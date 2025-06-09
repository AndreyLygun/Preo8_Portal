<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Role;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Orchid\Platform\Models\User;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Layouts\Rows;
use Throwable;

class RolePermissionLayout extends Rows
{
    /**
     * @var User|null
     */
    private $user;
    // Какие разрешения может выдывать пользователь с данными разрешениями (
    private $access = [
        'platform.systems.roles' => ['platform.systems.roles'],
        'platform.portal.renters' => ['platform.portal.', 'platform.renter.', 'platform.requests.'],
        'platform.renter.users' => ['platform.renter.', 'platform.requests.']
    ];

    /**
     * The screen's layout elements.
     *
     * @return Field[]
     * @throws Throwable
     *
     */
    public function fields(): array
    {
        $this->user = $this->query->get('user');
        return $this->generatedPermissionFields(
            $this->query->getContent('permission')
        );
    }

    private function generatedPermissionFields(Collection $permissionsRaw): array
    {
        $access = collect($this->access);
        $permissions = array_intersect(
            array_keys(array_filter(Auth::user()->permissions, fn($value) => $value == 1)),
            array_keys($this->access)
        );
        $canSetPermissions = $access->filter(fn($value, $key) => in_array($key, $permissions))->flatten();
        foreach($permissionsRaw as  $sectionKey => $section) {
            foreach($section as $controlKey => $control) {
                $allowed = false;
                foreach($canSetPermissions as $permission) {
                    if ($allowed = str_starts_with($control['slug'], $permission))
                        break;
                }
                if (!$allowed) {
                    unset($permissionsRaw[$sectionKey][$controlKey]);
                }
            }
        }
        return $permissionsRaw
            ->map(fn(Collection $permissions, $title) => $this->makeCheckBoxGroup($permissions, $title))
            ->flatten()
            ->toArray();
    }


    private function makeCheckBoxGroup(Collection $permissions, string $title): Collection
    {
        return $permissions
            ->map(fn(array $chunks) => $this->makeCheckBox(collect($chunks)))
            ->flatten()
            ->map(fn(CheckBox $checkbox, $key) => $key === 0
                ? $checkbox->title($title)
                : $checkbox)
            ->chunk(1)
            ->map(fn(Collection $checkboxes) => Group::make($checkboxes->toArray())
                ->alignEnd());
    }

    private function makeCheckBox(Collection $chunks): CheckBox
    {
        // При создании пользователя значение по умолчанию 'platform.index' должно быть true
        $active = $chunks->get('active');
        $placeholder = $chunks->get('description');
        if ($chunks->get('slug') == 'platform.index') {
            $placeholder = "Общий доступ на портал";
            if (!optional($this->user)->exists)
                $active = true;
        }

        return CheckBox::make('permissions.' . base64_encode($chunks->get('slug')))
            ->placeholder($placeholder)
            ->value($active)
            ->sendTrueOrFalse()
            ->indeterminate($this->getIndeterminateStatus(
                $chunks->get('slug'),
                $chunks->get('active')
            ));
    }

    private function getIndeterminateStatus($slug, $value): bool
    {
        return optional($this->user)->hasAccess($slug) === true && $value === false;
    }
}
