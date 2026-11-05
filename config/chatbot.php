<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prohibited chatbot terms
    |--------------------------------------------------------------------------
    |
    | Keep this list centralized. The Laravel policy and the Vue input check
    | both use these terms; neither component maintains its own copy.
    |
    */
    'prohibited_terms' => [
        'fuck',
        'fucking',
        'shit',
        'bullshit',
        'damn',
        'bitch',
        'ass',
        'asshole',
        'dick',
        'fucker',
        'motherfucker',
        'son of a bitch',
        'gago',
        'gaga',
        'gagu',
        'gagi',
        'tanga',
        'ulol',
        'putang ina',
        'putangina',
        'potangina',
        'tangina',
        'taena',
        'puta',
        'pota',
        'shuta',
        'shet',
        'tarantado',
        'panget',
        'bobo',
        'engot',
        'baliw',
        'bwesit',
        'bwisit',
        'bwiset',
        'bweset',
        'buset',
        'kingina',
        'mama mo',
        'nanay mo',
        'your mom',
        'what the hell',
        'what the hel',
        'wat the hell',
        'wat the hel',
        'what the heck',
        'wat the heck',

    ],

    'prohibited_message' => 'Please use respectful language. Offensive or prohibited words are not allowed.',

    // Client-facing location for collecting an approved original copy.
    'legal_office_pickup_location' => env(
        'LEGAL_OFFICE_PICKUP_LOCATION',
        'Bicol University West Campus, Gate 1, White Building',
    ),
];
