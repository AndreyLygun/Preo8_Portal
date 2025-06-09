<?php

namespace App\DRX\Screens;

use App\DRX\ApprovalStatus;
use App\DRX\DRXClient;
use App\DRX\Helpers\Functions;
use App\DRX\Helpers\MergingProperty;
use App\DRX\NewDRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Request;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use App\DRX\Helpers\NormalizeDate;

class BaseSRQScreen extends Screen
{
    use NormalizeDate;
    use MergingProperty;

//    Функция должна возвращать список полей данного объекта, которыми обменимваемся с сервисом интеграции Directum RX
//    public function fields()
//    {
//        $fields = [
//            'RequestState' => [
//                'type' => 'hidden',
//            ],
//            'Id' => [
//                'type' => 'hidden',        ],
//            'Renter.Name' => [
//                'type' => 'Label',
//                'title' => 'Название компании',
//                'default' => function() {Auth()->user()->DrxAccount->Name;}
//            ],
//            'Creator' => [
//                'type' => 'Label',
//                'title' => 'Автор заявки'
//            ]
//        ];
//    }

    public static $EntityType = "IServiceRequestsBaseSRQs";     // Имя сущности в сервисе интеграции, например IOfficialDocuments
    public static $Title = '';
    public static $Command = '';
    protected static $CollectionFields = [];                                // Список полей-коллекций, которые нужно пересоздавать в DRX заново при каждом сохранении
    protected static $ExpandFields = ["Author", "DocumentKind", "Renter"];  // Список полей-ссылок, которые нужно пересоздавать в DRX заново при каждом сохранении

    public $readOnly;
    public $entity;
    public $ApprovalStatus;

    public function ExpandFields()
    {
        return $this->MergeProperties('ExpandFields');
    }

    public function CollectionFields()
    {
        return $this->MergeProperties('CollectionFields');
    }

    // В наследуемых классах можно переопределить эту функцию, для предварительной обработки сохраняемой $this->Entity
    public function beforeSave()
    {
        // $this->Entity["FieldName"] = "NewValue";
    }

    public function BinaryFields(): array
    {
        return [];
    }

    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity()
    {
        if (!Functions::UserHasAccessTo(static::class)) {
            abort(403, "У вас нет доступа к данному виду заявок");
        }
        // Копируем заявку из другой
        if ($fromId = \request()->input('fromId')) {
            $drx = new NewDRXClient(static::$EntityType);
            try {
                $original = $drx->with($this->ExpandFields())->find($fromId);
                unset($original['Id']);
            } catch (\Exception $ex) {
                $original = [];
            }
        }
        // Заполняем поля по умолчанию
        $newEntity = [
            "Renter" => ['Name' => Auth()->user()->DrxAccount->Name],
            "Creator" => Auth()->user()->name,
            "RequestState" => "Draft",
            'ValidFrom' => Carbon::today()->addDay(),
            'ValidTill' => Carbon::today()->addDay(),
            'ValidOn' => Carbon::today()->addDay(),
        ];
        return array_merge($original ?? [], $newEntity, $this->entity ?? []);
    }

    public function query(int $id = null): iterable
    {
        try {
            $odata = new DRXClient();
            if ($id) {
                $currentEntity = $this->entity ?? [];
                $storedEntity = $odata->getEntity(static::$EntityType, $id, $this->ExpandFields());
                $entity = array_merge($storedEntity, $currentEntity);
            } else {
                $entity = $this->NewEntity();
            }
            if (in_array($entity['RequestState'], ['OnReview', 'Denied', 'Approved'])) {
                $ApprovalStatus = (new ApprovalStatus($odata))->Get($id);
            }
        } catch (GuzzleException $ex) {
            dd($ex->getResponse()->getBody()->getContents());
            Alert::error("При подключении к серверу произошла ошибка: " . stripcslashes($ex->getResponse()->getBody()->getContents()));

        }
        return [
            'entity' => $entity,
            'readOnly' => !in_array($entity['RequestState'], ['Draft', 'Denied']),
            'ApprovalStatus' => $ApprovalStatus ?? null
        ];
    }

    public function name(): ?string
    {
        if (isset($this->entity['Id']))
            return $this->entity['DocumentKind']['Name'] . ' (' . $this->entity['Id'] . ')';
        else
            return static::$Title . ' (новая)';
    }

    public function commandBar(): iterable
    {
        if (!isset($this->entity["RequestState"])) return [];
        $commands = [];

        switch ($this->entity["RequestState"]) {
            case 'Draft':
                if (isset($this->entity["Id"]))
                    $commands[] = Button::make("Отправить на согласование")->method("SubmitToApproval");
                $commands[] = Button::make("Сохранить")->method("Save")->type(Color::PRIMARY);
                break;
            case 'Active':
                break;
            case 'OnReview':
                $commands[] = Button::make('Отменить согласование')->method('AbortFlowTask');
                break;
            case 'Approved':
                break;
            case 'Denied':
                $commands[] = Button::make("Отправить на согласование")->method("SubmitToApproval");
                $commands[] = Button::make("Сохранить")->method("Save");
                break;
        }
//        $commands[]  = DropDown::make('Действия')->list($dropdownList);
        return $commands;
    }

    //TODO: исправить сохранение инициатора заявки: сейчас сохраняется арендатор вместо сотрудника
    public function SaveToDRX($submitToApproval = false, $entity = null)
    {
        $this->entity = $entity ?? \request()->get('entity');
        $this->beforeSave();
        $this->entity['Creator'] = Auth()->user()->name;
        $this->entity['CreatorMail'] = Auth()->user()->email;

        $odata = new DRXClient();
        $entity = $odata->saveEntity(static::$EntityType, $this->entity, $this->ExpandFields(), $this->CollectionFields());
        // Сохраняем бинарные данные
        foreach ($this->BinaryFields() as $binaryField) {
            if (\request()->hasFile($binaryField)) {
                $file = \request()->file($binaryField);
                $encoded = base64_encode($file->getContent());
                $odata->from(self::$EntityType."({$entity["Id"]})/$binaryField")->patch(['Value' => $encoded]);
            }
        }
        if ($submitToApproval) {
            $odata->callAPIfunction('ServiceRequests/StartDocumentReviewTask', ['requestId' => $entity['Id']]);
        }
        return $entity;
    }

    public function Save()
    {
        try {
            $this->entity = $this->SaveToDRX();
            Toast::info("Успешно сохранено");
            return redirect(route(Request::route()->getName()) . "/" . $this->entity['Id']);
        } catch (GuzzleException $ex) {
            Alert::error("При сохранении заявки произошла ошибка: " . stripcslashes($ex->getResponse()->getBody()->getContents()));
        }
    }

    public function SubmitToApproval($message = null)
    {
        $message = $message ?? "Заявка сохранена и отправлена на согласование";
        try {
            $this->SaveToDRX(true);
            Toast::info($message);
            return redirect(route('drx.srqlist'));
        } catch (GuzzleException $ex) {
            Alert::error("При сохранении заявки произошла ошибка: " . stripcslashes($ex->getResponse()->getBody()->getContents()));
        }
        return redirect()->route();
    }

    public function AbortFlowTask()
    {
        try {
            $odata = new DRXClient();
            $odata->callAPIfunction('ServiceRequests/AbortDocumentReviewTask', [
                'requestId' => $this->entity['Id'],
                'Reason' => Carbon::now()->toDateTimeString() . ' Сотрудник арендатора ' . auth()->user()->name . ' отменил согласование'
            ]);
            Toast::info('Запрос на отмену согласования отправлен.');
            return redirect(route('drx.srqlist'));
        } catch (GuzzleException $ex) {
            Alert::error("При отмене согласования произошла ошибка: " . stripcslashes($ex->getResponse()->getBody()->getContents()));
        }
    }


    public function layout(): iterable
    {
        if (isset($this->error)) {
            return [
                Layout::rows([
                    Label::make('error.Message'),
                    Label::make('error.Code')
                ])->title('Ошибка!')
            ];
        }
        $state = sprintf("&nbsp<span class='%s'>%s</span>", $this->entity['RequestState'], __($this->entity['RequestState']));
        $layout = [];
        if (in_array($this->entity['RequestState'], ['OnReview', 'Denied', 'Approved'])) {
            $layout[] = Layout::accordion([
                "Статус заявки: " . $state => [
                    Layout::view('ApprovalStateView', ['reviewStatus' => $this->ApprovalStatus]),
                ]
            ])->open('')->stayOpen(false);
        }
        $layout[] = Layout::rows([
            Input::make("entity.RequestState")->type("hidden"),
            Input::make("entity.Id")->type("hidden"),
            Label::make("entity.Renter.Name")->title("Название компании")->horizontal(),
            Label::make("entity.Creator")->title("Автор заявки")->horizontal()
        ]);
        return $layout;
    }
}
