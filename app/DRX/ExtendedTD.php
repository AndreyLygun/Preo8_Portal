<?php

declare(strict_types=1);

namespace App\DRX;

use Orchid\Screen\TD;
use Closure;

class ExtendedTD extends TD {
    protected $get_cssClass;

    public function cssClass(Closure $closure) : self
    {
        $this->get_cssClass = $closure;
        return $this;
    }

    public function buildTd($repository, ?object $loop = null)
    {
        $value = $this->render ? $this->handler($repository, $loop) : $repository->getContent($this->name);
        return view('orchid.td', [
            'align'   => $this->align,
            'value'   => $value,
            'render'  => $this->render,
            'slug'    => $this->sluggable(),
            'width'   => $this->width,
            'colspan' => $this->colspan,
            'css_class' => $this->get_cssClass ? ($this->get_cssClass)($repository) : "",
        ]);
    }


}
