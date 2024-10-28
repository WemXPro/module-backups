<?php

return [

    'name' => 'Backups',
    'icon' => 'https://imgur.png',
    'author' => 'GIGABAIT',
    'version' => '1.0.8',
    'wemx_version' => '>=2.2.0',

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
