<?php


namespace App\DRX\Helpers;


trait MergingProperty
{
    // Этот метод объединяет в массив свойств $propName текущего экземпляра со всеми свойствами $propName предков
    // Например, если класс Base содержит свойство $Color = ['Red', 'Green'],
    // а  его потомок класс Child extends Base содержит свойство $Color = ['White', 'Black']
    // то метод MergeProperties('Color') вернёт ['Red', 'Green', 'White', 'Black'] (порядок элементов не гарантируется)
    public function MergeProperties($propertyName)
    {
        // Получаем класс текущего объекта
        $class = new \ReflectionClass($this);
        $merged = $class->getProperty($propertyName)->getValue($class); // Начинаем с текущего значения
        // Идем по всем родительским классам
        while ($class = $class->getParentClass()) {
            // Получаем значение свойства $property из родительского класса
            if ($class->hasProperty($propertyName)) {
                $propertyValue = $class->getProperty($propertyName)->getValue($this);
                $merged = array_merge($merged, $propertyValue); // Добавляем к массиву
            }
        }
        return array_unique($merged);
    }
}
