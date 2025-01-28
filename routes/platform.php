<?php

declare(strict_types=1);

use App\Orchid\Screens\Examples\ExampleActionsScreen;
use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;

use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;

use App\Orchid\Screens\DRX\SiteSettingsScreen;
use App\Orchid\Screens\DRX\EntitiesListScreen;

use App\Orchid\Screens\DRX\Pass4VisitorsScreen;             // Разовый пропуск
use App\Orchid\Screens\DRX\PermanentPass4EmployeeScreen;    // Пропуск для сотрудника
use App\Orchid\Screens\DRX\Permission4EmployeeScreen;       // Допуск для сотрудника
use App\Orchid\Screens\DRX\StopPermanentPass4EmployeeScreen;// Блокировка пропуска сотрудника
use App\Orchid\Screens\DRX\Pass4VisitorCarScreen;           // Разовый автопропуск
use App\Orchid\Screens\DRX\PermanentPass4CarScreen;         // Псстоянный автопропуск
use App\Orchid\Screens\DRX\StopPermanentPass4CarScreen;     // Блокировка автопропуска
use App\Orchid\Screens\DRX\WorkPermissionScreen;                  // Заявка на выполнение работ

use App\Orchid\Screens\DRX\Pass4AssetsMovingScreen;         // Разовый ввоз-вывоз ТМЦ
use App\Orchid\Screens\DRX\Pass4AssetsInternalMovingScreen; // Разовое внутреннее перемещение ТМЦ
use App\Orchid\Screens\DRX\Pass4PermanentAssetsMovingScreen;// Разовое Перемещение ТМЦ



use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main

Route::screen("/srq/settings", SiteSettingsScreen::class)
    ->name('drx.sitesettings');


Route::screen("/srq/list", EntitiesListScreen::class)->name('drx.srqlist');

// Люди
Route::screen("/srq/IPass4VisitorDto/{id?}", Pass4VisitorsScreen::class)->name('drx.Pass4Visitors');
Route::screen("/srq/IPermanentPass4EmployeeDto/{id?}", PermanentPass4EmployeeScreen::class)->name('drx.PermanentPass4Employee');
Route::screen("/srq/IStopPermanentPass4EmployeeDto/{id?}", StopPermanentPass4EmployeeScreen::class)->name('drx.StopPermanentPass4Employee');
Route::screen("/srq/IPermission4EmployeeDto/{id?}", Permission4EmployeeScreen::class)->name('drx.Permission4Employee');
Route::screen("/srq/IWorkPermissionDto/{id?}", WorkPermissionScreen::class)->name('drx.WorkPermission');

// Машины
Route::screen("/srq/IPass4VisitorCarDto/{id?}", Pass4VisitorCarScreen::class)->name('drx.Pass4VisitorCar');
Route::screen("/srq/IPermanentPass4CarDto/{id?}", PermanentPass4CarScreen::class)->name('drx.PermanentPass4Car');
Route::screen("/srq/IStopPermanentPass4CarDto/{id?}", StopPermanentPass4CarScreen::class)->name('drx.StopPermanentPass4Car');
// ТМЦ
Route::screen("/srq/IPass4AssetsMovingDto/{id?}", Pass4AssetsMovingScreen::class)->name('drx.Pass4AssetsMoving');
Route::screen("/srq/IPass4AssetsInternalMovingDto/{id?}", Pass4AssetsInternalMovingScreen::class)->name('drx.Pass4AssetsInternalMoving');
Route::screen("/srq/IPass4PermanentAssetsMovingDto/{id?}", Pass4PermanentAssetsMovingScreen::class)->name('drx.Pass4PermanentAssetsMoving');



//Route::screen("/srq/IPermanentPass4CarsDto/{id?}", PermanentPass4CarsSRQScreen::class)
//    ->name('drx.PermanentPass4Carsto');






Route::screen("/srq/renters", \App\Orchid\Screens\DRX\DRXAccountListScreen::class)
    ->name('drx.renters');
Route::screen("/srq/renter/{drxAccount}", \App\Orchid\Screens\DRX\DRXAccountScreen::class)
    ->name('drx.renter');

Route::screen('/main', EntitiesListScreen::class)
    ->name('platform.main');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

// Example...
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Example Screen'));

Route::screen('/form/examples/fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('/form/examples/advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');
Route::screen('/form/examples/editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('/form/examples/actions', ExampleActionsScreen::class)->name('platform.example.actions');

Route::screen('/layout/examples/layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('/charts/examples/charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('/cards/examples/cards', ExampleCardsScreen::class)->name('platform.example.cards');

//Route::screen('idea', Idea::class, 'platform.screens.idea');
