<?php

namespace App\DRX\Layouts;

use App\DRX\Helpers\Databooks;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Columns;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class PermanentPass4EmployeeListener extends Listener
{
    protected $targets = [];//['ChangeCar', 'ChangeDrivers'];

    protected function layouts(): iterable
    {
        $readOnly = false;
        $changeCar = true; //$this->query['entity']['ChangeCar']??'NoChange' !== 'NoChange';
        $changeDrivers = true; //$this->query['entity']['ChangeDrivers']??'NoChange' !== 'NoChange';
        $layout[] = Layout::rows([
            Select::make('ChangeCar')
                ->title('Изменение автомобиля')->horizontal()->value('NoChange')
                ->options([
                    'Add' => 'Добавить автомобиль на парковку',
                    'Remove' => 'Исключить автомобиль с парковки',
                    'NoChange' => 'Без изменений',
                ])
                ->disabled($readOnly),
            Input::make('entity.CarModel')->title('Модель автомобиля')
                ->horizontal()->required()
                ->canSee($changeCar)
                ->disabled($readOnly),
            Input::make('entity.CarNumber')
                ->title('Номер автомобиля')->horizontal()
                ->canSee($changeCar)
                ->required()->disabled($readOnly),
            Select::make("entity.NeedPrintedPass")
                ->title('Изготовить ламинированный пропуск')->horizontal()->hr()
                ->canSee($changeCar)
                ->empty('')->required()
                ->options(Databooks::GetYesNo())
                ->disabled($readOnly),
            Select::make('ChangeDrivers')
                ->title('Изменение водителей')->horizontal()->value('NoChange')
                ->options([
                    'Add' => 'Добавить водителей в пропуск на парковку',
                    'Remove' => 'Исключить водителей с пропуска на парковку',
                    'NoChange' => 'Без изменений',
                ])
                ->disabled($readOnly),
            TextArea::make('visitors')
                ->title('Водители')->horizontal()
                ->canSee($changeDrivers)
                ->help('Если вы хотите добавить водителей в пропуск, укажите их ФИО')
                ->rows(3),
        ]);
        return $layout;
    }


    public function handle(Repository $repository, Request $request): Repository
    {
        dd(1);
    }
}
