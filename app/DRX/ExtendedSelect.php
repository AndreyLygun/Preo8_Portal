<?php

declare(strict_types=1);

namespace App\DRX;

use Orchid\Screen\Fields\Select;

class ExtendedSelect extends Select {
    protected $view = 'orchid.extendedSelect';
    public function intValue(bool $isInt=true) : self
    {
        $this->set('isInt', $isInt);
        return $this;
    }

}
