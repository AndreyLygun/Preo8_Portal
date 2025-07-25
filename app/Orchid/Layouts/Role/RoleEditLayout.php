<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Role;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class RoleEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('role.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name'))
                ->help(__('Role display name')),

            Input::make('role.slug')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Slug'))
                ->placeholder(__('Slug'))
                ->help(__('Actual name in the system')),

            Input::make('role.mandatory_level')
                ->type('integer')
                ->max(1000)
                ->required()
                ->title('Уровень доступа')
                ->placeholder()
                ->help('Пользователь видит роли и привелегии, уровень доступа которых равны или ниже его роли'),

            Input::make('role.description')
                ->type('text')
                ->required()
                ->title('Описание роли')
        ];
    }
}
