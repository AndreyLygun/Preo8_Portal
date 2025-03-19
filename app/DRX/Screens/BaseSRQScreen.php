<?php

namespace App\DRX\Screens;

use App\DRX\ApprovalStatus;
use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use App\DRX\Helpers\NormalizeDate;

class BaseSRQScreen extends Screen
{
    use NormalizeDate;

    protected $EntityType = "IServiceRequestsBaseSRQs";     // Имя сущности в сервисе интеграции, например IOfficialDocuments
    protected $CollectionFields;                      // Список полей-коллекций, которые нужно пересоздавать в DRX заново при каждом сохранении
    protected $Title = '';
    public $readOnly;
    public $entity;
    public $ApprovalStatus;

    // Возвращает список полей-ссылок и полей-коллекций, который используются в форме. Нужен, чтобы OData-API вернул значения этих полей
    // Как правило, перекрытый метод в классе-наследнике добавляет свои поля к результату метода из класса-предка
    public function ExpandFields()
    {
        return ["Author", "DocumentKind", "Renter"];
    }

    public function CollectionFields()
    {
        return [];
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
        return [
            "Renter" => ['Name' => Auth()->user()->DrxAccount->Name],
            "Creator" => Auth()->user()->name,
            "RequestState" => "Draft",
            'ValidFrom' => Carbon::today()->addDay(),
            'ValidTill' => Carbon::today()->addDay(),
            'ValidOn' => Carbon::today()->addDay(),
        ];
    }

    public function query(int $id = null): iterable
    {

        try {
            $odata = new DRXClient();
            if ($id) {
                $currentEntity = $this->entity??[];
                $storedEntity  = $odata->getEntity($this->EntityType, $id, $this->ExpandFields());
                $entity = array_merge($storedEntity, $currentEntity );
            } else {
                $entity = $this->NewEntity();
            }
            if (in_array($entity['RequestState'], ['OnReview', 'Denied', 'Approved'])) {
                $ApprovalStatus = (new ApprovalStatus($odata))->Get($id);
            }
        } catch (GuzzleException $ex) {
            abort($ex->getCode(), $ex->getMessage());
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
            return $this->Title . ' (новая)';
    }

    public function commandBar(): iterable
    {
        if (!isset($this->entity["RequestState"])) return [];
        $buttons = [];
        switch ($this->entity["RequestState"]) {
            case 'Draft':
                if (isset($this->entity["Id"]))
                    $buttons[] = Button::make("Отправить на согласование")->method("SubmitToApproval");
                $buttons[] = Button::make("Сохранить")->method("Save");
                break;
            case 'Active':
                break;
            case 'OnReview':
                break;
            case 'Approved':
                break;
            case 'Denied':
                $buttons[] = Button::make("Отправить на согласование")->method("SubmitToApproval");
                $buttons[] = Button::make("Сохранить")->method("Save");
                break;
        }
        return $buttons;
    }

    //TODO: исправить сохранение инициатора заявки: сейчас сохраняется арендатор вместо сотрудника
    public function SaveToDRX($submitToApproval = false, $entity = null)
    {
        $this->entity = $entity ?? \request()->get('entity');
        $this->beforeSave();
        $this->entity['Creator'] = Auth()->user()->name;
        $this->entity['CreatorMail'] = Auth()->user()->email;

        $odata = new DRXClient();
        $entity = $odata->saveEntity($this->EntityType, $this->entity, $this->ExpandFields(), $this->CollectionFields());
        // Сохраняем бинарные данные

        foreach ($this->BinaryFields() as $binaryField) {
            if (\request()->hasFile($binaryField)) {
                $file = \request()->file($binaryField);
                $encoded = base64_encode($file->getContent());
                $odata->from("{$this->EntityType}({$this->entity["Id"]})/$binaryField")->patch(['Value' => $encoded]);
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
        } catch (GuzzleException $ex) {
            Alert::error("При сохранении заявки произошла ошибка: " . stripcslashes($ex->getResponse()->getBody()->getContents()));
        }
    }

    public function SubmitToApproval($message = null)
    {
        $message = $message??"Заявка сохранена и отправлена на согласование";
        try {
            $this->SaveToDRX(true);
            Toast::info($message);
            return redirect(route('drx.srqlist'));
        } catch (GuzzleException $ex) {
            Alert::error("При сохранении заявки произошла ошибка: " . stripcslashes($ex->getResponse()->getBody()->getContents()));
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
