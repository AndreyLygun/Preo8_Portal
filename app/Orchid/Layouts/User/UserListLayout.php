<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\DrxAccount;
use Illuminate\Support\Facades\Auth;
use Orchid\Platform\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Persona;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class UserListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'users';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('name', 'Имя')
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) => new Persona($user->presenter())),

            TD::make('email', __('Email'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()),

            TD::make('updated_at', __('Last edit'))
                ->sort()
                ->render(fn (User $user) => $user->updated_at->toDateTimeString()),

            TD::make('drx_account_id', __('Арендатор'))
                ->sort()
                ->render(fn (User $user) => $user->DrxAccount?->Name),

        ];
    }

    protected function textNotFound(): string
    {
        return __('Сотрудники не найдены');
    }

    /**
     * @return string
     */
    protected function subNotFound(): string
    {
        return '';
    }
}
