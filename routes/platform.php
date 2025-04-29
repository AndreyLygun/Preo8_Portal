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

use App\DRX\Screens\SiteSettingsScreen;
use App\DRX\Screens\EntitiesListScreen;
use App\DRX\Screens\ParkingListScreen;

use App\DRX\Screens\People\VisitorsScreen;             // Разовый пропуск
use App\DRX\Screens\People\EmployeeScreen;    // Пропуск для сотрудника
use App\DRX\Screens\People\AdditionalPermissionScreen;       // Допуск для сотрудника
use App\DRX\Screens\People\WorkPermissionScreen;                  // Заявка на выполнение работ

//use App\DRX\Screens\People\StopPermanentPass4EmployeeScreen;// Блокировка пропуска сотрудника

use App\DRX\Screens\Cars\VisitorCarScreen;           // Разовый автопропуск
use App\DRX\Screens\Cars\ChangePermanentParkingScreen;


use App\DRX\Screens\Assets\AssetsInOutScreen;         // Разовый ввоз-вывоз ТМЦ
use App\DRX\Screens\Assets\AssetsInternalScreen; // Разовое внутреннее перемещение ТМЦ
use App\DRX\Screens\Assets\AssetsPermanentScreen;// Разовое Перемещение ТМЦ

use App\DRX\Screens\DRXAccountListScreen;
use App\DRX\Screens\DRXAccountScreen;

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


//foreach (config('srq.requestKinds') as $kind => $property) {
//    Route::screen("/srq/I{$kind}Dto/{id?}", $property['screen'])->name("drx1.{$kind}");
//}

// Main
Route::screen("/srq/settings", SiteSettingsScreen::class)->name('drx.sitesettings');
Route::screen("/srq/list", EntitiesListScreen::class)->name('drx.srqlist');
Route::screen("/srq/parking", ParkingListScreen::class)->name('drx.parking');

// Люди
Route::screen("/srq/IPass4VisitorDto/{id?}", VisitorsScreen::class)->name('drx.Pass4Visitors');
Route::screen("/srq/IPermanentPass4EmployeeDto/{id?}", EmployeeScreen::class)->name('drx.PermanentPass4Employee');
Route::screen("/srq/IPermission4EmployeeDto/{id?}", AdditionalPermissionScreen::class)->name('drx.Permission4Employee');
Route::screen("/srq/IWorkPermissionDto/{id?}", WorkPermissionScreen::class)->name('drx.WorkPermission');
//Route::screen("/srq/IStopPermanentPass4EmployeeDto/{id?}", StopPermanentPass4EmployeeScreen::class)->name('drx.StopPermanentPass4Employee');

// Машины
Route::screen("/srq/IPass4VisitorCarDto/{id?}", VisitorCarScreen::class)->name('drx.Pass4VisitorCar');
//Route::screen("/srq/IPermanentPass4CarDto/{id?}", PermanentCarScreen::class)->name('drx.PermanentPass4Car');
//Route::screen("/srq/IStopPermanentPass4CarDto/{id?}", ChangePermanentPass4CarScreen::class)->name('drx.ChangePermanentPass4Car');
Route::screen("/srq/IChangePermanentParkingDto/{id?}", ChangePermanentParkingScreen::class)->name('drx.ChangePermanentParking');

// ТМЦ
Route::screen("/srq/IPass4AssetsMovingDto/{id?}", AssetsInOutScreen::class)->name('drx.Pass4AssetsMoving');
Route::screen("/srq/IPass4AssetsInternalMovingDto/{id?}", AssetsInternalScreen::class)->name('drx.Pass4AssetsInternalMoving');
Route::screen("/srq/IPass4AssetsPermanentMovingDto/{id?}", AssetsPermanentScreen::class)->name('drx.Pass4AssetsPermanentMoving');



//Route::screen("/srq/IPermanentPass4CarsDto/{id?}", PermanentPass4CarsSRQScreen::class)->name('drx.PermanentPass4Carsto');

Route::screen("/srq/renters", DRXAccountListScreen::class)->name('drx.renters');
Route::screen("/srq/renter/{drxAccount}", DRXAccountScreen::class)->name('drx.renter');

Route::screen('/main', EntitiesListScreen::class)->name('platform.main');

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
