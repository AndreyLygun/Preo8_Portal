<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Request;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\ModalToggle;


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
    public $entity;


    // Возвращает список полей-ссылок и полей-коллекций, который используются в форме. Нужен, чтобы OData-API вернул значения этих полей
    // Как правило, перекрытый метод в классе-наследнике добавляет свои поля к результату метода из класса-предка
    public function ExpandFields()
    {
        $ExpandFields = ["Author", "DocumentKind", "Renter"];
        return $ExpandFields;
    }

    public function CollectionFields()
    {
        return [];
    }


    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity()
    {
        $entity = [
            "Renter" => ['Name' => Auth()->user()->DrxAccount->Name],
            "Creator" => Auth()->user()->name,
            "RequestState" => "Draft",
            'ValidFrom' => Carbon::today()->addDay(),
            'ValidTill' => Carbon::today()->addDay(),
            'ValidOn' => Carbon::today()->addDay(),

        ];
        return $entity;
    }

    public function query($id = null): iterable
    {
        if ($id) {
            $odata = new DRXClient();
            try {
                $entity = $odata->getEntity($this->EntityType, $id, $this->ExpandFields());
            } catch (GuzzleException $ex) {
                dd($ex);
                return [
                    'error' => [
                        'Message' => $ex->getMessage()
                    ]
                ];
            }
        } else {
            $entity = $this->NewEntity();
        }

        return ["entity" => $entity];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        if (isset($this->entity['Id']))
            return $this->entity['DocumentKind']['Name'] . ' (' . $this->entity['Id'] . ')';
        else
            return $this->Title . ' (новая)';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $buttons = [];
        if (!isset($this->entity["RequestState"])) return $buttons;
//        $buttons[] = Button::make("Копировать");
        switch ($this->entity["RequestState"]) {
            case 'Draft':
                if (isset($this->entity["Id"])) {
//                    $buttons[] = Button::make("Удалить")->method("Delete")->confirm('Удалить заявку?');
                    $buttons[] = Button::make("Отправить на согласование")->method("SubmitToApproval");
                }
                $buttons[] = Button::make("Сохранить")->method("Save");
                break;
            case 'Active':
                $buttons[] = Button::make("Одобрено")->disabled();
                break;
            case 'Obsolete':
                $buttons[] = Button::make("Устарел")->disabled();
                break;
            case 'OnReview':
                $buttons[] = Button::make("На рассмотрении")->disabled();
                break;
            case 'Prelimenary':
                break;
            case 'Declined':
                $buttons[] = Button::make("Отказ")->disabled();
                break;
        }
        return $buttons;
    }


    //TODO: исправить сохранение инициатора заявки: сейчас сохраняется арендатор вместо сотрудника
    public function SaveToDRX($submitToApproval = false)
    {
        $this->entity['Creator'] = Auth()->user()->name;
        $this->entity['CreatorMail'] = Auth()->user()->email;
        $odata = new DRXClient();
        //  dd(json_encode($this->entity));
        //  dd($this->EntityType, json_encode($this->entity), $this->ExpandFields(), $this->CollectionFields());
        $entity = $odata->saveEntity($this->EntityType, $this->entity, $this->ExpandFields(), $this->CollectionFields());
        if ($submitToApproval) {
            $odata->callAPIfunction('ServiceRequests/StartDocumentReviewTask', ['requestId' => $entity['Id']]);
        }
        return $entity;
    }

    public function Save()
    {
        $this->entity = request()->get('entity');
        $this->entity = $this->SaveToDRX();
        Toast::info("Успешно сохранено");
        return redirect(route(Request::route()->getName()) . "/" . $this->entity['Id']);
    }

    public function SubmitToApproval()
    {
        $this->entity = request()->get('entity');
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

    public function asyncGetApprovalState(): array
    {
//        $odata = new DRXClient();
//        dd(123);
//        $state = $odata->callAPIfunction('ServiceRequests/GetApprovalStatus(requestId=172)')->get();
//        dd($state);
        return [
            'xml' => 'state',
        ];
    }

    public function layout(): iterable
    {
        if (isset($this->query()['error'])) {
            return [
                Layout::rows([
                    Label::make('error.message'),
                    Label::make('error.errnum')
                ])->title('Ошибка!')
            ];
        }
        $state = config('srq.LifeCycles')[$this->entity['RequestState']];
        return [
            Layout::modal('StatusDialog', [
                Layout::rows([
                    Label::make('xml')
                ]),
            ])->async('asyncGetApprovalState'),
            Layout::rows([
                Input::make("entity.Id")->type("hidden"),
                ModalToggle::make($state)
                    ->modal('StatusDialog'),
//                Label::make("entity.RequestState")->title("Состояние заявки")->horizontal(),
                Label::make("entity.Renter.Name")->title("Название компании")->horizontal(),
                Label::make("entity.Creator")->title("Автор заявки")->horizontal(),

            ])
        ];
    }
}
