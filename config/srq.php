<?php


return [

    /*
    |--------------------------------------------------------------------------
    | RequestKinds
    | Виды заявок, который существуют на портале)
    | Используется:
    | - в файле routes/platform, чтобы
    | - в кнопке "Создать заявку..." в списке "Все заявки" (если command не заполнено, то команда на создание не создаётся)
    | - фильтре "Тип заявки" в списке "Все заявки"
    | - в permisssion на экране редактирования сотрудника (чтобы давать право на создание заявки)
     */
    'requests' => [
        \App\DRX\Screens\People\VisitorsScreen::class,
        \App\DRX\Screens\People\EmployeeScreen::class,
        \App\DRX\Screens\People\AdditionalPermissionScreen::class,
        \App\DRX\Screens\People\WorkPermissionScreen::class,
        \App\DRX\Screens\Cars\VisitorCarScreen::class,
        \App\DRX\Screens\Cars\ChangePermanentParkingScreen::class,
        \App\DRX\Screens\Assets\AssetsInOutScreen::class,
        \App\DRX\Screens\Assets\AssetsInternalScreen::class,
        \App\DRX\Screens\Assets\AssetsPermanentScreen::class,
    ],

    // Адрес Directum RX
    'url' => env('DRX_URL', 'http://preo8/Integration/odata/'),

    'MovingDirection' => [
        'MovingIn' => 'Ввоз',
        'MovingOut' => 'Вывоз',
        'CarryingIn' => 'Внос',
        'CarryingOut' => 'Вынос',
    ],

//    'RequestState' => [
//        'Draft' => 'Черновик',
//        'OnReview' => 'На рассмотрении',
//        'Approved' => 'Одобрен',
//        'Denied' => 'Отказано',
//        'Done' => 'Исполнен',
//          'Closed' => 'Закрыт'
//    ]
];
