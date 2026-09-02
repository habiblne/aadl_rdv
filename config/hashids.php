<?php

return [
    'default' => 'rdv',

    'connections' => [
        'rdv' => [
            'salt' => env('APP_KEY', 'aadl-rdv').':rdv',
            'length' => 10,
        ],
    ],
];
