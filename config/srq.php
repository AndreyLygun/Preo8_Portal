<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | DocumentKinds
    |--------------------------------------------------------------------------
    |
    | Виды документов (DocumentKinds, который используются на портале)
    | Используетсz в фильтрах на странице со списками
    |
     */

    'DocumentKinds' => [
            33 => 'Разовый гостевой пропуск ',
            34 => 'Разовый автомобильный пропуск',
            35 => 'Перемещение ТМЦ'
    ],

    'LifeCycles' => [
        "Active" => "Действующий",
        "Draft" => "Черновик",
        "OnReview" => "На рассмотрении",
        "Declined" => "Отказ",
        "Approved" => "Одобрен",
        "Obsolete" => "Устарел",
    ],

    'MovingDirection' => [
        'MoveIn' => 'Ввоз',
        'MoveOut' => 'Вывоз',
        'CarryIn' => 'Внос',
        'CarryOut' => 'Вынос',
    ],

    'RequestState' => [
        'Draft' => 'Черновик',
        'OnReview' => 'На рассмотрении',
        'Approved' => 'Одобрен',
        'Denied' => 'Отказано',
        'Done' => 'Исполнен'
    ]
];
