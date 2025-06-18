<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\DRX\Helpers\Functions;
use App\Orchid\Layouts\Role\RolePermissionLayout;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserPasswordLayout;
use App\Orchid\Layouts\User\UserRoleLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Orchid\Access\Impersonation;
use Orchid\Platform\Models\Role;
use Orchid\Platform\Models\User;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use function Laravel\Prompts\error;

class UserEditScreen extends Screen
{
    /**
     * @var User
     */
    public $user;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(User $user): iterable
    {
        $user->load(['roles']);
        $currentUser = Auth::user();
        if (!$currentUser->hasAccess('platform.portal.renters') &&
                isset($user['drx_account_id']) &&
                    $currentUser['drx_account_id'] != $user['drx_account_id'])
                        abort(403, 'Пользователь не найден');

        if (!$user->exists) {
            if (\request()->has('renter') && Auth::user()->hasAccess('platform.portal.renters')) {// Если в реквесте указан арендатор, добавляем сотрудника к этому арендатору
                $user->drx_account_id = request()->input('renter');
            }
            else    // Иначае задаём арендатора таким же, как у текущего сотрудника
                $user->drx_account_id = Auth::user()->drx_account_id;
        }
        return [
            'user' => $user,
            'permission' => $user->getStatusPermission(),
        ];
    }


    public function name(): ?string
    {
        return $this->user->exists ? 'Edit User' : 'Create User';
    }

    public function description(): ?string
    {
        return 'User profile and privileges, including their associated role.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.renter.users',
            'platform.portal.renters'
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Impersonate user'))
                ->icon('bg.box-arrow-in-right')
                ->confirm(__('You can revert to your original state by logging out.'))
                ->method('loginAs')
                ->canSee($this->user->exists && \request()->user()->id !== $this->user->id && Auth::user()->roles()->where('slug', 'superadmin')->exists()),

            Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->confirm('После удаления пользователя он потеряет доступ на портал, но заявки, созданные им, будут доступны для других сотрудников')
                ->method('remove')
                ->canSee($this->user->exists && Auth::user()->id !== $this->user->id),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    /**
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::block(UserEditLayout::class)
                ->title(__('Profile Information'))
                ->description(__('Update your account\'s profile information and email address.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserPasswordLayout::class)
                ->title(__('Password'))
                ->description('Вы можете не сообщать пароль пользователю. В этом случае он сможет воспользоваться процедурой восстановления пароля')
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserRoleLayout::class)
                ->title(__('Roles'))
                ->description(__('A Role defines a set of tasks a user assigned the role is allowed to perform.'))
                ->canSee(Auth::user()->hasAccess('platform.systems.roles'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(RolePermissionLayout::class)
                ->title(__('Permissions'))
                ->description(__('Allow the user to perform some actions that are not provided for by his roles'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

        ];
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(User $user, Request $request)
    {
        $user->drx_account_id = $this->user->drx_account_id;
        $request->validate([
            'user.email' => [
                'required',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
        ]);
        $permissions = collect($request->get('permissions'))
            ->map(fn($value, $key) => [base64_decode($key) => $value])
            ->collapse()
            ->toArray();
        $permissions['platform.systems.attachment'] = "1";
        $permissions['platform.index'] = "1";
        $user->when($request->filled('user.password'), function (Builder $builder) use ($request) {
            $builder->getModel()->password = Hash::make($request->input('user.password'));
        });
        $user->phone = $request->input('user.phone');
        $user->fill($request->collect('user')->except(['password', 'permissions', 'roles'])->toArray())
             ->fill(['permissions' => $permissions])
             ->save();
        $user->replaceRoles($request->input('user.roles'));
        Toast::info(__('User was saved.'));
        return redirect()->route('platform.systems.users.edit', ['user' => $user->id]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     *
     */
    public function remove(User $user)
    {
        $renter_id = $user->drx_account_id;
        $user->delete();
        Toast::info(__('User was removed'));
        if (Auth::user()->drx_account_id == $user->drx_account_id)
            return redirect()->route('platform.systems.users');
        else
            return redirect()->route('drx.renter', ['drxAccount' => $renter_id]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginAs(User $user)
    {
        Impersonation::loginAs($user);

        Toast::info(__('You are now impersonating this user'));

        return redirect()->route(config('platform.index'));
    }
}
