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

    public $entity;
    public $users;

    public function query(?DrxAccount $drxAccount = null): iterable
    {
        $currentUser = auth()->user();
        $employes = User::where('drx_account_id', $drxAccount['id'])->get();
        return ["entity" => $drxAccount, 'users' => $employes];
    }

    public function name(): ?string
    {
        return "Сведения об арендаторе и его сотрудниках";
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $buttons = [];
        $buttons[] = Button::make("Проверить пароль")
            ->method("TestConnection")->class('btn btn-primary');
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
                Input::make("entity.DRX_Login")
                    ->title("Логин в Directum")->horizontal()
                    ->help('Должен совпадать с логином арендатора в Directum RX ("Сервисные заявки" -> "Арендаторы"'),
                Input::make("entity.DRX_Password")
                    ->title("Пароль в Directum")->horizontal()->type('password')
                    ->help('Должен совпадать с паролем арендатора в Directum RX ("Сервисные заявки" -> "Арендаторы"'),
            ]),
            UserListLayout::class
        ];
    }

    public function permission(): ?iterable
    {
        return ['platform.systems.renters'];
    }


    public function TestConnection()
    {
        //TODO сделать проверку запроса от имени редактируемой учётки
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
