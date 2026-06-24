<?php
declare(strict_types=1);

final class HttpClient
{
    public function postJson(string $url, array $payload, array $headers = [], int $timeout = 10): array
    {
        return $this->request('POST', $url, $payload, $headers, $timeout);
    }

    public function getJson(string $url, array $headers = [], int $timeout = 10): array
    {
        return $this->request('GET', $url, null, $headers, $timeout);
    }

    private function request(string $method, string $url, ?array $payload, array $headers, int $timeout): array
    {
        if ($url === '') {
            throw new RuntimeException('URL API belum dikonfigurasi.');
        }

        $defaultHeaders = ['Accept: application/json'];
        if ($payload !== null) {
            $defaultHeaders[] = 'Content-Type: application/json';
        }

        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || $value === '') {
                continue;
            }

            if (is_int($name)) {
                $defaultHeaders[] = (string) $value;
                continue;
            }

            $defaultHeaders[] = "{$name}: {$value}";
        }

        if (function_exists('curl_init')) {
            return $this->requestWithCurl($method, $url, $payload, $defaultHeaders, $timeout);
        }

        return $this->requestWithStream($method, $url, $payload, $defaultHeaders, $timeout);
    }

    private function requestWithCurl(string $method, string $url, ?array $payload, array $headers, int $timeout): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException($error ?: 'Request API gagal.');
        }

        return $this->decodeResponse((string) $body, $status);
    }

    private function requestWithStream(string $method, string $url, ?array $payload, array $headers, int $timeout): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $payload === null ? '' : json_encode($payload),
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = file_get_contents($url, false, $context);
        $status = 0;

        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int) $match[1];
        }

        if ($body === false) {
            throw new RuntimeException('Request API gagal.');
        }

        return $this->decodeResponse($body, $status);
    }

    private function decodeResponse(string $body, int $status): array
    {
        $json = json_decode($body, true);

        if (!is_array($json)) {
            throw new RuntimeException('Response API bukan JSON valid.');
        }

        if ($status >= 400) {
            $message = $json['message'] ?? 'Request API ditolak.';

            if (isset($json['error'])) {
                if (is_array($json['error'])) {
                    $message = $json['error']['message'] ?? json_encode($json['error']);
                } else {
                    $message = $json['error'];
                }
            }

            throw new RuntimeException((string) $message);
        }

        return $json;
    }
}
