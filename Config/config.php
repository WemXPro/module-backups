<?php

return [

    'name' => 'Backups',
    'icon' => 'https://imgur.png',
    'author' => 'GIGABAIT',
    'version' => '1.0.5',
    'wemx_version' => '1.9.1',

    'elements' => [
        'admin_menu' =>
            [
                [
                    'name' => 'backups::messages.backups',
                    'icon' => '<i class="fas fa-database"></i>',
                    'href' => '/admin/backups',
                    'style' => '',
                ],
            ],

    ],

];
