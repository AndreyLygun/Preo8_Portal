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

    public function query(): iterable
    {
        return ['entities' => DrxAccount::all()];

    }


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
            Link::make('Добавить арендатора')->route('drx.renter')
        ];
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
                ExtendedTD::make("ИНН", "ИНН")->render(
                    fn (DrxAccount $DRXAccount) => Link::make($DRXAccount->INN)->route('drx.renter', $DRXAccount)
                )
            ])
        ];
    }
}
