<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;
use App\Models\DrxAccount;

class UserEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
//        dd(Auth::user()->hasAccess('platform.systems.users'));
        return [
            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name'))
                ->disabled(!Auth::user()->hasAccess('platform.systems.users')),
            Input::make('user.email')
                ->type('email')
                ->required()
                ->title(__('Email'))
                ->placeholder(__('Email'))
                ->disabled(!Auth::user()->hasAccess('platform.systems.users')),
            Select::make('user.drx_account_id')
            ->fromModel(DrxAccount::class, 'name')
            ->title('Арендатор')
                ->disabled(!Auth::user()->hasAccess('platform.systems.users'))
        ];
    }
}
