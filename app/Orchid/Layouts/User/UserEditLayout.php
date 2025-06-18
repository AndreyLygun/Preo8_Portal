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
        $canChangeAnotherUsers = Auth::user()->hasAnyAccess(['platform.renter.users', 'platform.portal.renters']);

        return [
            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required($canChangeAnotherUsers)
                ->title('Имя')
                ->readonly(!$canChangeAnotherUsers)
                ->placeholder('Имя Фамилия'),
            Input::make('user.email')
                ->type('email')
                ->required($canChangeAnotherUsers)
                ->title(__('Email'))
                ->readonly(!$canChangeAnotherUsers)
                ->placeholder(__('Email')),
            Input::make('user.phone')
                ->type('text')
                ->mask('+7 (999) 999-99-99')
                ->title(__('Телефон')),
            Select::make('user.drx_account_id')
                ->fromModel(DrxAccount::class, 'name')
                ->title('Арендатор')
                ->disabled(true)
        ];
    }
}
