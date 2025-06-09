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
        return [
            Button::make('Обновить')->method('update')->confirm('С сервера Директум будет получен список арендаторов вместе со статусом'),
            Link::make('Создать')->route('drx.renter')
        ];
    }

    public function update() {
        $odata = new DRXClient();
        $result = $odata->getList('IServiceRequestsRenters', ['Login'], 'Name');
        dd($result);
    }

    public function permission(): ?iterable
    {
        return ['platform.portal.renters'];
    }


    public function layout(): iterable
    {
        return [
            Layout::table("entities", [
                ExtendedTD::make("id", "№")->render(
                    fn (DrxAccount $DRXAccount) => Link::make($DRXAccount->id)->route('drx.renter', $DRXAccount)
                ),
                ExtendedTD::make("Name", "Название")->render(
                    fn (DrxAccount $DRXAccount) => Link::make($DRXAccount->Name)->route('drx.renter', $DRXAccount)
                ),
                ExtendedTD::make("DRX_Login", "Логин для Directum")->render(
                    fn (DrxAccount $DRXAccount) => Link::make($DRXAccount->DRX_Login)->route('drx.renter', $DRXAccount)
                )
            ])
        ];
    }
}
