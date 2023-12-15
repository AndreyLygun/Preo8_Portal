<?php

declare(strict_types=1);

namespace App\DRX;

use Orchid\Screen\Fields\Matrix;

class ExtendedMatrix extends Matrix {
    protected $view = 'orchid.extendedMatrix';

    public function readonly(bool $readonly) : self
    {
        $this->set('readonly', $readonly);
        return $this;
    }

}
