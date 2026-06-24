<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/HttpClient.php';

final class PaymentQrService
{
    public function __construct(
        private readonly array $config,
        private readonly HttpClient $http = new HttpClient()
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false) && ($this->config['endpoint'] ?? '') !== '';
    }

    public function createPayment(array $loan, array $member, array $book): array
    {
        $paymentId = (int) ($loan['id_peminjaman'] ?? $loan['id_pengajuan'] ?? 0);
        $referencePrefix = isset($loan['id_pengajuan']) ? 'REQ' : 'LOAN';
        $reference = $referencePrefix . '-' . $paymentId . '-' . date('YmdHis');
        $amount = (int) ($this->config['amount'] ?? 0);

        if (!$this->isEnabled()) {
            return $this->createLocalQrPayment($reference, $amount, $member, $book);
        }

        $payload = [
            'reference' => $reference,
            'amount' => $amount,
            'currency' => (string) ($this->config['currency'] ?? 'IDR'),
            'merchant_id' => (string) ($this->config['merchant_id'] ?? ''),
            'customer' => [
                'name' => (string) $member['nama'],
                'phone' => (string) $member['no_hp'],
            ],
            'description' => 'Peminjaman buku: ' . (string) $book['judul'],
        ];

        $response = $this->http->postJson(
            (string) $this->config['endpoint'],
            $payload,
            $this->authHeaders(),
            (int) ($this->config['timeout'] ?? 10)
        );

        $qrUrl = $response['qr_url']
            ?? $response['qr_image_url']
            ?? $response['qr_code_url']
            ?? $response['qrImageUrl']
            ?? null;

        if (!$qrUrl && !empty($response['qr_image_base64'])) {
            $qrUrl = 'data:image/png;base64,' . $response['qr_image_base64'];
        }

        return [
            'reference' => (string) ($response['reference'] ?? $reference),
            'amount' => (int) ($response['amount'] ?? $amount),
            'currency' => (string) ($response['currency'] ?? ($this->config['currency'] ?? 'IDR')),
            'qr_url' => $qrUrl,
            'qr_string' => $response['qr_string']
                ?? $response['qr_payload']
                ?? $response['qrCode']
                ?? $response['qr_content']
                ?? null,
            'status' => (string) ($response['status'] ?? 'pending'),
            'raw_response' => $response,
        ];
    }

    private function authHeaders(): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');

        return $apiKey === '' ? [] : ['Authorization' => "Bearer {$apiKey}"];
    }

    private function createLocalQrPayment(string $reference, int $amount, array $member, array $book): array
    {
        $currency = (string) ($this->config['currency'] ?? 'IDR');
        $paymentUrl = trim((string) ($this->config['payment_url'] ?? ''));
        $qrisPayload = trim((string) ($this->config['qris_static_payload'] ?? ''));
        $qrString = $paymentUrl !== ''
            ? $paymentUrl
            : ($qrisPayload !== ''
            ? $this->buildQrisPayload($qrisPayload, $amount)
            : $this->buildInstructionPayload($reference, $amount, $currency, $member, $book));

        return [
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'qr_url' => $this->buildQrImageUrl($qrString),
            'qr_string' => $qrString,
            'status' => 'pending',
            'raw_response' => [
                'source' => $paymentUrl !== ''
                    ? 'local_scan_payment'
                    : ($qrisPayload !== '' ? 'local_qris_payload' : 'local_payment_instruction'),
            ],
        ];
    }

    private function buildInstructionPayload(string $reference, int $amount, string $currency, array $member, array $book): string
    {
        $lines = [
            'E-PERPUS PAYMENT',
            'Reference: ' . $reference,
            'Nama: ' . (string) $member['nama'],
            'No HP: ' . (string) $member['no_hp'],
            'Buku: ' . (string) $book['judul'],
            'Nominal: ' . ($amount > 0 ? "{$currency} {$amount}" : 'Konfirmasi petugas'),
            'Status: Menunggu verifikasi',
        ];

        return implode("\n", $lines);
    }

    private function buildQrImageUrl(string $payload): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=10&data=' . rawurlencode($payload);
    }

    private function buildQrisPayload(string $payload, int $amount): string
    {
        $normalized = preg_replace('/6304[0-9A-Fa-f]{4}$/', '', $payload) ?? $payload;

        if ($amount > 0) {
            $normalized = $this->removeEmvTag($normalized, '54');
            $amountValue = number_format($amount, 2, '.', '');
            $amountTag = '54' . str_pad((string) strlen($amountValue), 2, '0', STR_PAD_LEFT) . $amountValue;
            $normalized = $this->insertEmvTagBefore($normalized, $amountTag, ['58', '59', '60']);
        }

        $withoutCrc = $normalized . '6304';

        return $withoutCrc . strtoupper(str_pad(dechex($this->crc16Ccitt($withoutCrc)), 4, '0', STR_PAD_LEFT));
    }

    private function removeEmvTag(string $payload, string $tag): string
    {
        $result = '';
        $offset = 0;
        $length = strlen($payload);

        while ($offset + 4 <= $length) {
            $currentTag = substr($payload, $offset, 2);
            $valueLength = (int) substr($payload, $offset + 2, 2);
            $chunkLength = 4 + $valueLength;

            if ($chunkLength <= 4 || $offset + $chunkLength > $length) {
                return $payload;
            }

            $chunk = substr($payload, $offset, $chunkLength);
            if ($currentTag !== $tag) {
                $result .= $chunk;
            }
            $offset += $chunkLength;
        }

        return $result . substr($payload, $offset);
    }

    private function insertEmvTagBefore(string $payload, string $newTag, array $beforeTags): string
    {
        $result = '';
        $offset = 0;
        $length = strlen($payload);
        $inserted = false;

        while ($offset + 4 <= $length) {
            $currentTag = substr($payload, $offset, 2);
            $valueLength = (int) substr($payload, $offset + 2, 2);
            $chunkLength = 4 + $valueLength;

            if ($chunkLength <= 4 || $offset + $chunkLength > $length) {
                return $payload . $newTag;
            }

            if (!$inserted && in_array($currentTag, $beforeTags, true)) {
                $result .= $newTag;
                $inserted = true;
            }

            $result .= substr($payload, $offset, $chunkLength);
            $offset += $chunkLength;
        }

        if (!$inserted) {
            $result .= $newTag;
        }

        return $result . substr($payload, $offset);
    }

    private function crc16Ccitt(string $payload): int
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return $crc;
    }
}
