<?php
declare(strict_types=1);

return [
    'recommendation' => [
        'enabled' => false,
        'endpoint' => 'https://example.com/recommend',
        'api_key' => '',
    ],
    'payment' => [
        'enabled' => false,
        'endpoint' => 'https://example.com/payment/qr',
        'api_key' => '',
        'merchant_id' => '',
        'amount' => 10000,
        'currency' => 'IDR',
        'qris_static_payload' => '',
        'base_url' => 'http://localhost/E-Perpustakaan',
    ],
    'gmail' => [
        'enabled' => false,
        'token' => '',
        'user_id' => 'me',
        'query' => 'from:payment@example.com newer_than:7d',
        'verification_secret' => 'ubah-token-ini',
    ],
    'email' => [
        'enabled' => false,
        'driver' => 'smtp',
        'from_email' => 'akun-gmail-pengirim@gmail.com',
        'from_name' => 'E-Perpustakaan',
        'timeout' => 8,
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'akun-gmail-pengirim@gmail.com',
            'password' => 'ISI_APP_PASSWORD_GMAIL_DI_SINI',
            'encryption' => 'tls',
        ],
        'gmail' => [
            'token' => '',
            'user_id' => 'me',
        ],
    ],
];
