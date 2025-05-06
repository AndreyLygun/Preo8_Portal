<?php


namespace App\DRX\Helpers;


trait MergingProperty
{
    // Этот метод объединяет в массив свойств $propName текущего экземпляра со всеми свойствами $propName предков
    // Например, если свойство $color
    public function MergingProperties($propName)
    {
        // Получаем класс текущего объекта
        $class = new ReflectionClass($this);
        $merged = $class->getProperty($propName)->getValue($class); // Начинаем с текущего значения

        // Идем по всем родительским классам
        while ($class = $class->getParentClass()) {
            // Получаем значение свойства $property из родительского класса
            if ($class->hasProperty($propName)) {
                $propertyValue = $class->getProperty($propName)->getValue($this);
                $merged = array_merge($merged, $propertyValue); // Добавляем к сумме
            }
        }
        return $merged;
    }
}
