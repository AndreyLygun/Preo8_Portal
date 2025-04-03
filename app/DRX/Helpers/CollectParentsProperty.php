<?php


namespace App\DRX\Helpers;

use ReflectionClass;

trait CollectParentsProperty
{

    public function CollectParentsProperty($name)
    {
        $property = [];
        $class = new ReflectionClass($this);
        do {
            if ($class->hasProperty($name)) {
                $property = $class->getProperty($name);
                $property->setAccessible(true); // Делаем свойство доступным для чтения
                $info[] = $property->getValue($this);
            }
        } while ($class);

        // Возвращаем конкатенацию информации
        return array_reverse($property);
    }

}
