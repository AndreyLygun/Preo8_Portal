<?php

namespace App\DRX\Screens;

use App\DRX\DRXClient;
use App\DRX\ExtendedTD;
use App\Models\DrxAccount;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DRXAccountListScreen extends Screen
{

    public $entities;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return ['entities' => DrxAccount::all()];

    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Арендаторы';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [Button::make('Обновить')->method('update')->confirm('С сервера Директум будет получен список арендаторов вместе со статусом')];
    }

    public function update() {
        $odata = new DRXClient();
        $result = $odata->getList('IServiceRequestsRenters', ['Login'], 'Name');
        dd($result);
    }

    public function permission(): ?iterable
    {
        return ['platform.systems.renters'];
    }


    public function layout(): iterable
    {
        return [
            Layout::table("entities", [
                ExtendedTD::make("id", "№"),
                ExtendedTD::make("Name", "Название"),
                ExtendedTD::make("DRX_Login", "Логин для Directum"),
//                ExtendedTD::make("DRX_Password", "Пароль для Directum"),
                ExtendedTD::make('')
                    ->render(fn (DrxAccount $DRXAccount) => Link::make('')
                        ->route('drx.renter', $DRXAccount)
                        ->icon('bs.pencil'))
            ])
        ];
    }
}
