<?php
$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => ['NAME' => 'Настройки'],
    ],
    'PARAMETERS' => [
        'MENU_CODE' => [
            'PARENT'   => 'SETTINGS',
            'NAME'     => 'Код меню',
            'TYPE'     => 'STRING',
            'DEFAULT'  => 'main',
        ],
        'ACTIVE_ONLY' => [
            'PARENT'   => 'SETTINGS',
            'NAME'     => 'Только активные пункты',
            'TYPE'     => 'CHECKBOX',
            'DEFAULT'  => 'Y',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
        'CACHE_GROUPS' => [
            'PARENT'  => 'CACHE_SETTINGS',
            'NAME'    => 'Учитывать права доступа',
            'TYPE'    => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
    ],
];
