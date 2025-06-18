<?php

namespace App\DRX\Screens;

use App\DRX\DRXClient;
use App\DRX\NewDRXClient;
use App\Models\DrxAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;
use App\Orchid\Layouts\User\UserListLayout;


class DRXAccountScreen extends Screen
{



    public ?DrxAccount $renter = null;
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
        $buttons = [
//            Button::make("Проверить пароль")->method("TestConnection")
//                ->canSee($this->renter->exists)
//                ->class('btn btn-primary'),
            Link::make('Добавить сотрудника')->class('btn btn-primary')
                ->canSee($this->renter->exists)
                ->route('platform.systems.users.create', ['renter'=>$this->renter->id]),
            Button::make("Сохранить")->method("Save")->class('btn btn-primary'),

        ];
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
                Input::make("renter.Name")
                    ->title("Название компании")->horizontal()
                    ->required(),
                Input::make("renter.INN")
                    ->title("ИНН компании")->horizontal()
                    ->required(),
                Input::make("renter.DRX_Login")
                    ->title("Логин в Directum")->horizontal()
                    ->help('Логин должен совпадать с логином Арендатора в Directum RX')
                    ->canSee($this->renter->exists && Auth::user()->hasAccess('platform.systems.roles'))
                    ->required(),
                Input::make("renter.DRX_Password")
                    ->title("Пароль в Directum")->horizontal()
                    //->type('password')
                    ->help('Пароль должен совпадать с паролем арендатора в Directum RX ')
                    ->canSee($this->renter->exists && Auth::user()->hasAccess('platform.systems.roles'))
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
        $validated = Validator::make($request->input('renter'), [
            'Name' => ['required', Rule::unique('drx_accounts')->ignore($this->renter->id)],
            'INN' => ['required', 'integer', Rule::unique('drx_accounts')->ignore($this->renter->id),],
        ])->validated();
        $this->renter->fill($validated);
        if ($this->renter->exists) {
            $this->renter->fill($validated);
            $this->UpdateRenterInDRX($this->renter->DRX_Login, $this->renter);
        } else {
            $RenterInDRX =  $this->CreateRenterInDrx($validated);
            $this->renter->fill([
                'Name' => $RenterInDRX['Name'],
                'DRX_Login' => $RenterInDRX['DRX_Login'],
                'DRX_Password' => $RenterInDRX['DRX_Password']
            ]);
        };
        $this->renter->save();
        Toast::info('Арендатор сохранён');
        return redirect()->route('drx.renter', $this->renter->id);
    }

    private function CreateRenterInDRX($validated): array {
        $client = new DRXClient();
        $numberOfDay = today()->diffInDays(Carbon::parse('2001-01-01')); //Количество дней с начала века
        $LoginName = 'preo8_' . $validated['INN'] . '_' .$numberOfDay; //Уникальный логин на основании названия системы, ИНН и количества дней
        $Password = bin2hex(random_bytes(10));              //Случайный пароль
        // с помоощью интеграционной функции создаём учётку арендатора в DRX .
        $client->callAPIfunction('Company/CreateLogin', [
            'loginName' => $LoginName,
            'password' => $Password
        ]);
        // Потом ищем созданный логин, ибо функция создания логина ничего не возаращает.
        $Login = $client->from('ILogins')->where(array(["LoginName", '=', $LoginName]))->get()[0];
        $renter = [
            'Name' => $validated['Name'],
            'Login' => ['Id' => $Login['Id']]
        ];
        $RenterInDRX = $client->saveEntity('IServiceRequestsRenters', $renter, 'Login');
        $RenterInDRX['DRX_Login'] = $LoginName;
        $RenterInDRX['DRX_Password'] = $Password;
        return $RenterInDRX;
    }

    private function UpdateRenterInDRX($RenterLoginName, $data){
        $client = new DRXClient();
        // Ищем в DRX арендатора с указанным именем учётки
        $RenterInDRX = ($client->from('IServiceRequestsRenters')->where('Login/LoginName', $RenterLoginName)->take(1)->get())[0];
        $RenterInDRX['Name'] = $data['Name'];
        $client->saveEntity('IServiceRequestsRenters', $RenterInDRX);
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
