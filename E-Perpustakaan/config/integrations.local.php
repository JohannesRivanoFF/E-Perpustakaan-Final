<?php
declare(strict_types=1);

return [
    'email' => [
        'enabled' => true,
        'driver' => 'smtp',
        'from_email' => 'vano16022004@gmail.com',
        'from_name' => 'E-Perpustakaan',
        'timeout' => 8,
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'vano16022004@gmail.com',
            'password' => 'cahwdxzenepjqrev', // tanpa spasi
            'encryption' => 'tls',
        ],
        'gmail' => [
            'token' => 'ISI_ACCESS_TOKEN_GMAIL_API_DI_SINI',
            'user_id' => 'me',
        ],
    ],
];