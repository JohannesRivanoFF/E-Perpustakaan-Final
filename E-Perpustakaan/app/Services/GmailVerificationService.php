<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/HttpClient.php';

final class GmailVerificationService
{
    private const API_BASE = 'https://gmail.googleapis.com/gmail/v1/users';

    public function __construct(
        private readonly array $config,
        private readonly HttpClient $http = new HttpClient()
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false) && ($this->config['token'] ?? '') !== '';
    }

    public function paymentReferenceExists(string $reference): bool
    {
        if (!$this->isEnabled() || $reference === '') {
            return false;
        }

        $userId = rawurlencode((string) ($this->config['user_id'] ?? 'me'));
        $query = trim((string) ($this->config['query'] ?? 'newer_than:7d') . ' "' . $reference . '"');
        $url = self::API_BASE . "/{$userId}/messages?q=" . rawurlencode($query) . '&maxResults=1';

        $response = $this->http->getJson($url, $this->authHeaders(), (int) ($this->config['timeout'] ?? 10));

        return !empty($response['messages']);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . (string) $this->config['token']];
    }
}
