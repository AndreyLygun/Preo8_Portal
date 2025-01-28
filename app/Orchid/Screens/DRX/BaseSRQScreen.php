<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;


function IdNameFunction($value) {
    return [$value['Id']=>$value['Name']];
}


class BaseSRQScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    protected $EntityType = "IServiceRequestsBaseSRQs";     // Имя сущности в сервисе интеграции, например IOfficialDocuments
    protected $CollectionFields;                      // Список полей-коллекций, которые нужно пересоздавать в DRX заново при каждом сохранении
    protected $Title = '';
    public $readOnly;
    public $entity;
    public $Sites;
    public $TimeSpans;
    public $reviewStatus;


    // Получаем самую ранюю дату исполнения заявки в зависимости от текущего времени
    public function EearliestDate($hour) {
        $currentHour = Carbon::now()->hour;
        if ($currentHour < $hour)
            return Carbon::today();
        else
            return Carbon::today()->addDay(1);
    }

    // преобразовывает дату в формат, требуемый
    public function NormalizeDate(string|array $fields) {
        if (is_array($fields)) {
            foreach ($fields as $field)
                if (isset($this->entity[$field]))
                    $this->entity[$field] = Carbon::parse($this->entity[$field])->format('Y-m-d\TH:i:00+03:00');
        } else {
            if (isset($this->entity[$fields]))
                $this->entity[$fields] = Carbon::parse($this->entity[$fields])->format('Y-m-d\TH:i:00+03:00');;
        }
    }

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

    public function beforeSave()
    {
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
        $odata = new DRXClient();
        try {
            if ($id) {
                $entity = $odata->getEntity($this->EntityType, $id, $this->ExpandFields());
                if (in_array($entity['RequestState'], ['OnReview', 'Denied', 'Approved'])) {
                    $odata->setEntityReturnType(false);
                    $response = $odata->callAPIfunction('ServiceRequests/GetApprovalStatus', ["requestId" => $id]);
                    $state = $response["\x00SaintSystems\OData\ODataResponse\x00decodedBody"]['value'];
                    $reviewStatus = str_replace(["\r\n", "{'status':", "'}"], '', $state);
                }
            } else {
                $entity = $this->NewEntity();
            }
            $Sites = Cache::rememberForever('Sites', function() use ($odata) {
                return $odata->from('IServiceRequestsSites')->get();
            });
            $TimeSpans = Cache::rememberForever('TimeSpans', function() use ($odata) {
                return $odata->from('IServiceRequestsTimeSpans')->get();
            });
            return [
                'entity' => $entity,
                'readOnly' => !in_array($entity['RequestState'], ['Draft', 'Denied']),
                'Sites' => $Sites,
                'TimeSpans' => $TimeSpans,
                'reviewStatus' => $reviewStatus??''
            ];
        } catch (GuzzleException $ex) {
            abort($ex->getCode(), $ex->getMessage());
        }
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
        $this->entity = $entity??\request()->get('entity');
        $this->beforeSave();
        $this->entity['Creator'] = Auth()->user()->name;
        $this->entity['CreatorMail'] = Auth()->user()->email;
        $odata = new DRXClient();
        $entity = $odata->saveEntity($this->EntityType, $this->entity, $this->ExpandFields(), $this->CollectionFields());
        if ($submitToApproval) {
            $odata->callAPIfunction('ServiceRequests/StartDocumentReviewTask', ['requestId' => $entity['Id']]);
        }
        return $entity;
    }

    public function Save()
    {
        $this->entity = $this->SaveToDRX();
        Toast::info("Успешно сохранено");
        return redirect(route(Request::route()->getName()) . "/" . $this->entity['Id']);
    }

    public function SubmitToApproval()
    {
        $this->SaveToDRX(true);
        Toast::info("Заявка сохранена и отправлена на согласование");
        return redirect(route('drx.srqlist'));
    }

    public function Delete()
    {
        $odata = new DRXClient();
        $odata->deleteEntity($this->EntityType, request('entity.Id'));
        Toast::info("Заявка удалена");
        return redirect(route('drx.srqlist'));
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
                "Статус заявки: " . $state => [Layout::view('ApprovalStateView', ['approval_state' => $this->reviewStatus])]
            ]);
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
