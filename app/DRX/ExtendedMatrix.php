<?php

declare(strict_types=1);

namespace App\DRX;

use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Matrix;

class ExtendedMatrix extends Matrix
{
    protected $view = 'orchid.extendedMatrix';


    public function readonly(bool $readonly = true): self
    {
        $this->set('readonly', $readonly);
        return $this;
    }

    public function addButton(Button $button) {
        $extraButtons = $this->get('extraButtons', []);
        $extraButtons[] = $button;
        $this->set('extraButtons', $extraButtons);
        return $this;
    }
}
