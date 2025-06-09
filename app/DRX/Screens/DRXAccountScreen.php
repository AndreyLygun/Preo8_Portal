<?php

namespace App\DRX\Screens;

use App\DRX\DRXClient;
use App\DRX\NewDRXClient;
use App\Models\DrxAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use App\Orchid\Layouts\User\UserListLayout;


class DRXAccountScreen extends Screen
{

    public $renter;
    public $users;

    public function query(?DrxAccount $drxAccount = null): iterable
    {
        $employees = User::where('drx_account_id', $drxAccount->id)->get();
        return ["renter" => $drxAccount, 'users' => $employees];
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
        $loginHint = $this->renter->exists ?
            'Логин должен совпадать с логином в Directum RX (ссылка "Логин" на странице арендатора в разделе "Сервисные заявки/Арендаторы")' :
            'Удобно использовать в качестве логина интернет-домен заказчика. Например, если сайт компании - www.preo8.ru, логином может быть preo8';
        $passwordHint = $this->renter->exists ?
            'Пароль должен совпадать с паролем в Directum RX (ссылка "Логин" на странице арендатора в разделе "Сервисные заявки/Арендаторы")' :
            'Вы можете использовать этот случайный пароль или создать свой.';

        return [
            Layout::rows([
                Input::make("renter.Name")
                    ->title("Название компании")->horizontal()
                    ->placeholder('ООО "Ромашка"')
                    ->required(),
                Input::make("renter.DRX_Login")
                    ->title("Логин в Directum")->horizontal()
                    ->placeholder('romashka')
                    ->help($loginHint)
                    ->required(),
                Input::make("renter.DRX_Password")
                    ->title("Пароль в Directum")->horizontal()
                    ->type('password')
                    ->help($passwordHint)
                    ->required()
                    ->value(bin2hex(random_bytes(10))),
            ]),
            UserListLayout::class
        ];
    }

    public function permission(): ?iterable
    {
        return ['platform.portal.renters'];
    }

    public function Save(Request $request)
    {
        $client = new NewDRXClient('');
        if (!$this->renter->exists) {
            $validated = $request->validate([
                'renter.Name' => ['required', 'unique:drx_accounts,Name'],
                'renter.DRX_Login' => ['required', 'unique:drx_accounts,DRX_Login'],
                'renter.DRX_Password' => ['required'],
            ]);
            dd($validated);
            $client->callAPIfunction('CreateLogin', [
                'loginName' => $validated['renter.DRX_Login'],
                'password' => $validated['renter.DRX_Password']
            ]);
        }
        $this->entity->fill(request('renter'))->save();
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
}
