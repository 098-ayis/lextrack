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
        'gago',
        'gaga',
        'tanga',
        'ulol',
        'putang ina',
        'tangina',
        'puta',
        'panget',
        'bobo',
        'engot',
        'baliw',
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
];
