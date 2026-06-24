<?php
declare(strict_types=1);

function integration_env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

$config = [
    'recommendation' => [
        'enabled' => filter_var(integration_env('RECOMMENDATION_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'endpoint' => integration_env('RECOMMENDATION_API_URL', ''),
        'api_key' => integration_env('RECOMMENDATION_API_KEY', ''),
        'timeout' => (int) integration_env('RECOMMENDATION_API_TIMEOUT', 5),
    ],
    'payment' => [
        'enabled' => filter_var(integration_env('PAYMENT_QR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'endpoint' => integration_env('PAYMENT_QR_API_URL', ''),
        'api_key' => integration_env('PAYMENT_QR_API_KEY', ''),
        'merchant_id' => integration_env('PAYMENT_QR_MERCHANT_ID', ''),
        'amount' => (int) integration_env('PAYMENT_QR_AMOUNT', 0),
        'currency' => integration_env('PAYMENT_QR_CURRENCY', 'IDR'),
        'qris_static_payload' => integration_env('PAYMENT_QR_QRIS_STATIC_PAYLOAD', ''),
        'base_url' => integration_env('PAYMENT_QR_BASE_URL', ''),
        'timeout' => (int) integration_env('PAYMENT_QR_TIMEOUT', 10),
    ],
    'gmail' => [
        'enabled' => filter_var(integration_env('GMAIL_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'token' => integration_env('GMAIL_ACCESS_TOKEN', ''),
        'user_id' => integration_env('GMAIL_USER_ID', 'me'),
        'query' => integration_env('GMAIL_PAYMENT_QUERY', 'newer_than:7d'),
        'verification_secret' => integration_env('GMAIL_VERIFICATION_SECRET', ''),
        'timeout' => (int) integration_env('GMAIL_API_TIMEOUT', 10),
    ],
    'email' => [
        'enabled' => filter_var(integration_env('EMAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'driver' => integration_env('EMAIL_DRIVER', 'gmail_api'),
        'from_email' => integration_env('EMAIL_FROM', 'noreply@eperpus.local'),
        'from_name' => integration_env('EMAIL_FROM_NAME', 'E-Perpustakaan'),
        'timeout' => (int) integration_env('EMAIL_TIMEOUT', 5),
        'gmail' => [
            'token' => integration_env('GMAIL_ACCESS_TOKEN', ''),
            'user_id' => integration_env('GMAIL_USER_ID', 'me'),
        ],
    ],
];

$localConfigPath = __DIR__ . '/integrations.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

return $config;
