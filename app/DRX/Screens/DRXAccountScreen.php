<?php

namespace App\DRX\Screens;

use App\DRX\DRXClient;
use App\Models\DrxAccount;
use App\Models\User;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use App\Orchid\Layouts\User\UserListLayout;


class DRXAccountScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */

    public $entity;


    // Используется для заполнения значений для новых сущностей (значения по-умолчанию).
    public function NewEntity()
    {
        $entity = [
            "Renter" => ['Name' => Auth()->user()->DrxAccount->Name],
            "Creator" => Auth()->user()->name,
            "RequestState" => "Draft"
        ];
        return $entity;
    }

    public function query(DrxAccount $drxAccount): iterable
    {
        $currentUser = auth()->user();
        $employee = User::where('drx_account_id', $drxAccount['id'])->get();
        return ["entity" => $drxAccount, 'users' => $employee];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return "Учётка";
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $buttons = [];
        $buttons[] = Button::make("Удалить")->method("Delete")->confirm('Удалить учётную запись?')->class('btn btn-light');
        $buttons[] = Button::make("Проверить")->method("TestConnection")->class('btn btn-primary');
        $buttons[] = Button::make("Сохранить")->method("Save")->class('btn btn-primary');
        return $buttons;
    }


    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make("entity.Name")->title("Название компании")->horizontal(),
                Input::make("entity.DRX_Login")->title("Логин в Directum")->horizontal(),
                Input::make("entity.DRX_Password")->title("Пароль в Directum")->type('password')->horizontal(),
            ]),
            UserListLayout::class
        ];
    }

    /**
     * @return mixed|object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */

    public function TestConnection()
    {
        //TODO сделать проверку запроса от имени редактируемой учётки
        Toast::info("Пока это заглушка проверки");
        $odata = new DRXClient();
        try {
            $result = $odata->from('IServiceRequestsBaseSRQs')->count();
        } catch (\Exception $ex) {
            Toast::error("Не удалось подключиться к серверу Directum: " . $ex->getMessage());
            return;
        }
        Toast::info("Проверка выполнена");
    }


    public function Save()
    {

        $this->entity->fill(request('entity'))->save();

//        return redirect(route(Request::route()->getName()) . "/" . $this->entity['Id']);
    }

    public function Delete()
    {

        Toast::info("Пока это заглушка удаления");
 //       return redirect(route('drx.srqlist'));
    }
}
