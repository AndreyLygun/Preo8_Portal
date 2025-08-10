<?php

declare(strict_types=1);

namespace App\DRX\Layouts;

use App\DRX\ExtendedMatrix;
use App\DRX\Helpers\Databooks;
use App\DRX\Helpers\ImportExcel;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class AssetMovingInOutLayout extends Rows
{
    public function fields(): array
    {
        {
            $readonly = false;
            $layout[] = Layout::rows([
                Select::make('entity.LoadingSite.Id')
                    ->title('Место разгрузки')->horizontal()
                    ->options(Databooks::GetSites('Loading'))->empty('')
                    ->required()->disabled($readonly),
                Input::make('entity.Floor')
                    ->title('Куда')
                    ->horizontal()
                    ->required()
                    ->help("Укажите блок, этаж, помещение")
                    ->disabled($readonly),
                Select::make("entity.Elevator")
                    ->title('Через грузовой лифт')->horizontal()
                    ->empty('')->required()
                    ->options(Databooks::GetYesNo())
                    ->disabled($this->readOnly),
                Select::make("entity.StorageRoom")
                    ->title('Через комнату временного хранения')->horizontal()
                    ->empty('')->required()
                    ->options(Databooks::GetYesNo())
                    ->disabled($this->readOnly),
            ])->title('Сведения о перемещении');

            $modalToggeButton = ModalToggle::make('Заполнить из Excel')
                ->modal('Excel')
                ->method('FillFromExcell', ['entity' => $this->entity])
                ->icon('bs.table');
            $clearButton = Button::make('Очистить')
                ->icon('full-screen')
                ->method('ClearInventory', ['entity' => $this->entity]);

            $layout[] = Layout::rows([
                Select::make("entity.BuildingMaterials")
                    ->title('Среди ТМЦ есть стройматериалы')->horizontal()
                    ->empty('')->required()
                    ->options(Databooks::GetYesNo())
                    ->disabled($this->readOnly),
                ExtendedMatrix::make('entity.Inventory')
                    ->columns(['Описание' => 'Name', 'Габариты' => 'Size', 'Количество' => 'Quantity', 'Примечание' => 'Note'])
                    ->readonly($readonly)
                    ->addButton($clearButton)
                    ->addButton($modalToggeButton),
            ])->title("Описание ТМЦ");

            $layout[] = ImportExcel::MakeModalExcel('Список ТМЦ', '/assets/inventory.xlsx');


            $layout[] = Layout::rows([
                Button::make(__("Save"))
                    ->method('saveCarrier')->class('btn btn-primary')
                    ->canSee(in_array($this->entity['RequestState'], ['OnReview', 'Approved'])),
                Label::make('')->value('Сведения о перевозчике можно внести или изменить в любой момент до фактического въезда')->class("small mt-0 mb-0")->canSee($this->entity['RequestState'] != 'Approved'),
                Input::make('entity.CarModel')
                    ->title('Модель автомобиля')->horizontal()
                    ->help("Если во время создания заявки модель и номер автомобиля неизвестны, их можно заполнить позже - до въезда"),
                Input::make('entity.CarNumber')
                    ->title('Номер автомобиля')->horizontal(),
                TextArea::make('entity.Visitors')
                    ->title('Грузчики (по одному человеку на строку)')->horizontal()->rows(3)
                    ->help("Если во время создания заявки имена грузчиков неизвестны, их можно указать позже - до въезда"),
            ])->title('Сведения о перевозчике');
            return $layout;
        }
    }
}
