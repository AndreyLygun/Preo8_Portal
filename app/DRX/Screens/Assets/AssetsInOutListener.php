<?php

namespace App\DRX\Screens\Assets;

use App\DRX\ExtendedMatrix;
use App\DRX\Helpers\Databooks;
use App\DRX\Helpers\ImportExcel;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class AssetsInOutListener extends Listener
{
    protected $targets = ['entity.MovingDirection'];
    protected function layouts(): iterable
    {

        $entity = $this->query->get('entity');
        $readOnly = $this->query->get('readOnly');
        $moving = $entity['MovingDirection'] ?? '';
        $out = str_contains($moving, 'Out');
        $in = str_contains($moving, 'In');
        $manual = str_contains($moving, 'Carrying') && ($in || $out);
        $garbage = str_contains($moving, 'Garbage') && ($in || $out);
        $sites = Databooks::GetSites('Loading');
        if ($garbage) $sites = $sites->filter(function (string $value, int $key) {
            return !str_contains($value, 'Турникеты');
        });
        if ($manual) $sites = $sites->filter(function (string $value, int $key) {
            return str_contains($value, 'Турникеты');
        });
        $timeSpans = Databooks::GetTimeSpans();
        if ($garbage) {
            $timeSpans = $timeSpans->filter(function (string $value, int $key) {
                return str_contains($value, 'мусор');
            });
        }
        if (!$garbage) {
            $timeSpans->filter(function (string $value, int $key) {
                return !str_contains($value, 'мусора');
            });
//            dd(!str_contains('Выходные с 08:00-23:00 (вывоз мусора)', 'мусора'));
//            dd($timeSpans);
        }
        $help = '"Внос/вынос (через турникеты)" - автоматическое согласование при вносе/выносе до 5 малогоабаритных (до 60 см) предметов через турникет. ' .
            'Для перемещения большего количества или более крупных предметов или стройматериалов выберите "Ввоз" или "Вывоз".';
        $layout[] = Layout::rows([
            Select::make('entity.MovingDirection')
                ->title('Направление перемещения')->horizontal()
                ->options(Databooks::GetMovingDirection())->empty('')
                ->help($help)
                ->required()->disabled($readOnly),
//
//            TextArea::make('')->title('')->readonly(true)->horizontal()->rows(5)
//                ->value("Ввоз и вывоз - обычная процедура согласования\nВнос и вынос - моментальное соглаосвание, максимум 5 малогабаритных предметов через турникет на 3-м этаже"),
            DateTimer::make('entity.ValidOn')->canSee($in || $out)
                ->title("Дата перемещения")->horizontal()
                ->format('d-m-Y')->serverFormat('d-m-Y')
                ->placeholder('Выберите дату')
                ->withQuickDates(['Сегодня' => Carbon::today(), 'Завтра' => Carbon::today()->addDay()])
                ->min(Carbon::today())
                ->help('При оформлении "на сегодня" учтите, что время согласования заявки может составить 3 часа')
                ->disabled($readOnly)->required(),
            Select::make('entity.LoadingSite.Id')->canSee($in || $out)
                ->title($manual ? 'Через' : ($out ? 'Место загрузки' : 'Место разгрузки'))->horizontal()
                ->options($sites)->empty('')//->value($manual?$sites->first():'')
                ->required()->disabled($readOnly),
            Input::make('entity.Floor')->canSee($in || $out)
                ->title($out ? 'Откуда' : 'Куда')
                ->horizontal()
                ->required()
                ->help("Укажите блок, этаж, помещение")
                ->disabled($readOnly),
            Select::make("entity.Elevator")->canSee(!$manual && ($in || $out))
                ->title('Через грузовой лифт')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($readOnly),
            Select::make('entity.ElevatorTimeSpan')->canSee(!$manual && ($in || $out))
                ->title('Время')->horizontal()
                ->options($timeSpans)
                ->empty('Выберите время')
                ->help($garbage?'':'Можно выбрать до трёх интервалов')
                ->multiple()->maximumSelectionLength($garbage?1:3)
                ->disabled($readOnly),
            Select::make("entity.StorageRoom")->canSee(!$manual && ($in || $out))
                ->title('Через комнату временного хранения')->horizontal()
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($readOnly),
        ])->title('Сведения о перемещении');

        $modalToggeButton = ModalToggle::make('Заполнить из Excel')
            ->modal('Excel')
            ->method('FillFromExcell', ['entity' => $entity])
            ->icon('bs.table');
        $clearButton = Button::make('Очистить')
            ->icon('full-screen')
            ->method('ClearInventory', ['entity' => $entity]);

        if ($in || $out)
            $layout[] = Layout::rows([
                Select::make("entity.BuildingMaterials")->canSee(!$manual && !$garbage)
                    ->title('Среди ТМЦ есть стройматериалы')->horizontal()
                    ->empty('')->required()
                    ->options(Databooks::GetYesNo())
                    ->disabled($readOnly),
                ExtendedMatrix::make('entity.Inventory')
                    ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity', 'Примечание' => 'Note'])
                    ->readonly($readOnly)
                    ->addButton($clearButton)
                    ->addButton($garbage?null:$modalToggeButton),
            ])->title($garbage?"Вид упаковки (мешок, коробка и т.д.) и количество":"Описание ТМЦ");

        $layout[] = ImportExcel::MakeModalExcel('Список ТМЦ', '/assets/inventory.xlsx');

        if (($in || $out) && !$manual)
            $layout[] = Layout::rows([
                Button::make(__("Save"))
                    ->method('saveCarrier')->class('btn btn-primary')
                    ->canSee(in_array($entity['RequestState'], ['OnReview', 'Approved'])),
                Label::make('')->value('Сведения о перевозчике можно внести или изменить в любой момент до фактического въезда')->class("small mt-0 mb-0")->canSee($entity['RequestState'] != 'Approved'),
                Input::make('entity.CarModel')
                    ->title('Модель автомобиля')->horizontal()
                    ->help("Если во время создания заявки модель и номер автомобиля неизвестны, их можно заполнить позже - до въезда"),
                Input::make('entity.CarNumber')
                    ->title('Номер автомобиля')->horizontal(),
                TextArea::make('entity.Visitors')
                    ->title('Грузчики (по одному человеку на строку)')->horizontal()->rows(3)
                    ->help("Если во время создания заявки имена грузчиков неизвестны, их можно указать позже - до въезда"),
            ])->title('Сведения о перевозчике');

        $layout[] = Layout::rows([TextArea::make('entity.Note')
            ->title("Примечание")->rows(10)->horizontal()
            ->disabled($readOnly)]);
        return $layout;
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        return $repository->set($request->all());
    }
}
