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
use Orchid\Screen\Layouts\Columns;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class PermanentPass4EmployeeListener extends Listener
{
    protected $targets = ['clear'];

    protected function layouts(): iterable
    {
        $layout[] = Layout::rows([
            Matrix::make('entity.Employees')->columns(['ФИО' => 'Name', 'Должность' => 'Position']),
            Button::make('Очистить')->confirm('Спиок сотрудников будет очищен. Продолжать?')->action('Clear'),
            Input::make('files')->type('file')->title('Заполнить из Excel'),
//            ModalToggle::make('Заполнить из файла Excel')
//                ->modal('Excel')
//                ->method('FillFromExcell')
//                ->icon('full-screen')
        ]);
//        $layout[] = Layout::modal('Excel', [
//            Layout::rows([
//                Input::make('files')->type('file')->title('Выберите файл Excel'),
//                Label::make()->title("Требования к файлу: первая строка: заголовок, первая колонка: ФИО, вторая колонка: должность"),
//            ]),
//        ]);
        return $layout;
    }


    public function handle(Repository $repository, Request $request): Repository
    {
        dd(1);
    }
}
