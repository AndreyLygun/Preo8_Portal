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
        return [
            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title('Имя')
                ->placeholder('Имя Фамилия')
                ->disabled(!Auth::user()->hasAccess('platform.renter.users')),
            Input::make('user.email')
                ->type('email')
//                ->required()
                ->title(__('Email'))
                ->placeholder(__('Email'))
                ->disabled(!Auth::user()->hasAccess('platform.renter.users')),
            Input::make('user.phone')
                ->type('text')
                ->mask('+7 (999) 999-99-99')
                ->title(__('Телефон')),
            Select::make('user.drx_account_id')
                ->fromModel(DrxAccount::class, 'name')
                ->empty()
                ->title('Арендатор')
                ->required()
                ->canSee(Auth::user()->hasAccess('platform.systems.renters'))
        ];
    }
}
