<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        //dd(Auth::user()->getStatusPermission());
        return [
            Menu::make("Все заявки")
                ->route('drx.srqlist')
                ->icon('bs.file-earmark-text'),

            Menu::make("Парковочные места")
                ->route('drx.parking')
                ->icon('bs.car-front'),

            Menu::make(__('Сотрудники'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Renters'))
                ->icon('bs.building')
                ->route('drx.renters')
                ->permission('platform.systems.renters')
                ->divider(),

            Menu::make(__('Roles'))
                ->icon('bs.lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),

//            Menu::make('Form Elements')
//                ->icon('bs.card-list')
//                ->route('platform.example.fields')
//                ->active('*/examples/form/*'),

            Menu::make(__('Бизнес-центр'))
                ->icon('bs.lock')
                ->route('drx.sitesettings')
                ->permission('platform.systems.renters')
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        $requestsPermissions = ItemPermission::group(__('Может создавать:'));
        foreach (config('srq.requests') as $kind) {
            $properties = get_class_vars($kind);
            $requestsPermissions = $requestsPermissions->addPermission("platform.requests.{$properties['EntityType']}", $properties["Title"] ?? $kind);
        }
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Может настраивать все доступы'))
                ->addPermission('platform.systems.renters', 'Может добавлять арендаторов'),
            ItemPermission::group('Доступ')
                ->addPermission('platform.renter.acccessAllRequests', 'Видит все заявки компании (не только свои)')
                ->addPermission('platform.renter.users', 'Управляет пользователями')
                ->addPermission('platform.renter.createAllRequests', 'Может создавать все заявки'),
            $requestsPermissions,
        ];
    }
}
