<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/HttpClient.php';

final class EmailNotificationService
{
    private const GMAIL_API_BASE = 'https://gmail.googleapis.com/gmail/v1/users';
    private const LOG_FILE = __DIR__ . '/../../logs/email_error.log';

    private function logError(string $message): void
    {
        $dir = dirname(self::LOG_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            self::LOG_FILE,
            date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function __construct(
        private readonly array $config,
        private readonly HttpClient $http = new HttpClient()
    ) {
    }

    public function send(string $to, string $subject, string $body): array
    {
        $this->logError("Attempting to send email to: $to, subject: $subject");

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->logError("Invalid email: $to");
            return ['status' => 'failed', 'message' => 'Email peminjam tidak valid.'];
        }

        if (!$this->isEnabled()) {
            $this->logError("Email service disabled");
            return ['status' => 'queued', 'message' => 'Pengiriman email belum diaktifkan.'];
        }

        $driver = strtolower((string) ($this->config['driver'] ?? 'gmail_api'));

        if ($driver === 'smtp') {
            return $this->sendWithSmtp($to, $subject, $body);
        }

        if ($driver !== 'gmail_api') {
            $this->logError("Unsupported driver: $driver");
            return ['status' => 'queued', 'message' => 'Driver email belum didukung. Gunakan gmail_api atau smtp.'];
        }

        return $this->sendWithGmailApi($to, $subject, $body);
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    private function sendWithGmailApi(string $to, string $subject, string $body): array
    {
        $token = $this->configString(['gmail', 'token'], $this->configString(['token']));

        if ($token === '' || str_contains($token, 'ISI_ACCESS_TOKEN')) {
            $this->logError("Gmail API token missing or placeholder");
            return ['status' => 'queued', 'message' => 'Token Gmail API belum diisi.'];
        }

        try {
            $userId = rawurlencode((string) ($this->config['gmail']['user_id'] ?? $this->config['user_id'] ?? 'me'));
            $url = self::GMAIL_API_BASE . "/{$userId}/messages/send";
            $this->http->postJson($url, [
                'raw' => $this->buildRawMessage($to, $subject, $body),
            ], [
                'Authorization' => 'Bearer ' . $token,
            ], max(3, (int) ($this->config['timeout'] ?? 5)));

            $this->logError("Email sent via Gmail API to $to");
            return ['status' => 'sent', 'message' => 'Email berhasil dikirim.'];
        } catch (Throwable $error) {
            $this->logError("Gmail API error: " . $error->getMessage());
            return ['status' => 'failed', 'message' => $error->getMessage()];
        }
    }

    private function sendWithSmtp(string $to, string $subject, string $body): array
    {
        // Cek OpenSSL (hanya sekali di awal)
        if (!extension_loaded('openssl')) {
            $this->logError("OpenSSL extension not loaded");
            return ['status' => 'failed', 'message' => 'Ekstensi OpenSSL PHP tidak aktif. SMTP via TLS/SSL membutuhkan OpenSSL.'];
        }

        $smtp = is_array($this->config['smtp'] ?? null) ? $this->config['smtp'] : [];
        $host = (string) ($smtp['host'] ?? 'smtp.gmail.com');
        $fromEmail = (string) ($this->config['from_email'] ?? '');
        $username = (string) ($smtp['username'] ?? $fromEmail);
        $password = preg_replace('/\s+/', '', (string) ($smtp['password'] ?? '')) ?? '';
        $timeout = max(5, (int) ($this->config['timeout'] ?? 8));

        $this->logError("SMTP config: host=$host, username=$username, timeout=$timeout");

        if ($username === '' || str_contains($username, 'akun-gmail-pengirim')) {
            $username = $fromEmail;
        }

        if ($username === '') {
            $this->logError("Username / from_email empty");
            return ['status' => 'queued', 'message' => 'Email pengirim belum diisi.'];
        }

        if ($password === '' || str_contains($password, 'ISI_APP_PASSWORD')) {
            $this->logError("App password empty or placeholder");
            return ['status' => 'queued', 'message' => 'App Password Gmail belum diisi di config/integrations.local.php.'];
        }

        // Coba beberapa kombinasi port & enkripsi
        $attempts = [
            ['port' => 587, 'encryption' => 'tls'],
            ['port' => 465, 'encryption' => 'ssl'],
        ];

        $lastError = '';

        foreach ($attempts as $attempt) {
            $port = $attempt['port'];
            $encryption = $attempt['encryption'];
            $socketHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;

            $this->logError("Attempting connection to $socketHost:$port with $encryption");

            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client($socketHost . ':' . $port, $errno, $errstr, $timeout);

            if (!is_resource($socket)) {
                $lastError = "Failed to connect to $socketHost:$port - errno=$errno, errstr=$errstr";
                $this->logError($lastError);
                continue; // coba port berikutnya
            }

            stream_set_timeout($socket, $timeout);
            $this->logError("Connected to $socketHost:$port");

            try {
                // SMTP handshake
                $this->smtpExpect($socket, [220]);
                $this->smtpCommand($socket, 'EHLO localhost', [250]);

                // STARTTLS jika enkripsi tls
                if ($encryption === 'tls') {
                    $this->smtpCommand($socket, 'STARTTLS', [220]);
                    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        throw new RuntimeException('STARTTLS failed.');
                    }
                    $this->logError("TLS enabled");
                    $this->smtpCommand($socket, 'EHLO localhost', [250]);
                }

                // Autentikasi
                $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
                $this->smtpCommand($socket, base64_encode($username), [334]);
                $this->smtpCommand($socket, base64_encode($password), [235]);
                $this->logError("Authentication successful");

                // Kirim email
                $fromEmail = $fromEmail !== '' ? $fromEmail : $username;
                $this->smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
                $this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
                $this->smtpCommand($socket, 'DATA', [354]);

                $rawMessage = $this->buildSmtpMessage($to, $subject, $body);
                fwrite($socket, $rawMessage . "\r\n.\r\n");
                $this->smtpExpect($socket, [250]);
                $this->smtpCommand($socket, 'QUIT', [221]);
                fclose($socket);

                $this->logError("Email sent successfully to $to via port $port");
                return ['status' => 'sent', 'message' => 'Email berhasil dikirim via SMTP Gmail.'];
            } catch (Throwable $error) {
                if (is_resource($socket)) {
                    fclose($socket);
                }
                $lastError = "SMTP error on port $port: " . $error->getMessage();
                $this->logError($lastError);
                // Lanjut ke port berikutnya
            }
        }

        // Jika semua percobaan gagal
        $this->logError("All SMTP attempts failed. Last error: $lastError");
        return ['status' => 'failed', 'message' => $this->friendlySmtpError($lastError)];
    }

    private function buildRawMessage(string $to, string $subject, string $body): string
    {
        return rtrim(strtr(base64_encode($this->buildSmtpMessage($to, $subject, $body)), '+/', '-_'), '=');
    }

    private function buildSmtpMessage(string $to, string $subject, string $body): string
    {
        $fromEmail = (string) ($this->config['from_email'] ?? 'noreply@eperpus.local');
        $fromName = trim((string) ($this->config['from_name'] ?? 'E-Perpustakaan'));
        $from = $fromName === '' ? $fromEmail : sprintf('%s <%s>', $this->encodeHeader($fromName), $fromEmail);

        $message = [
            'To: ' . $to,
            'From: ' . $from,
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $this->escapeSmtpBody($body),
        ];

        return implode("\r\n", $message);
    }

    private function escapeSmtpBody(string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalized);

        foreach ($lines as &$line) {
            if (str_starts_with($line, '.')) {
                $line = '.' . $line;
            }
        }
        unset($line);

        return implode("\r\n", $lines);
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value) !== 1) {
            return $value;
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function configString(array $path, string $default = ''): string
    {
        $value = $this->config;

        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }

            $value = $value[$key];
        }

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param resource $socket
     * @param array<int> $expectedCodes
     */
    private function smtpCommand($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpExpect($socket, $expectedCodes);
    }

    /**
     * @param resource $socket
     * @param array<int> $expectedCodes
     */
    private function smtpExpect($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException(trim($response) ?: 'Response SMTP tidak valid.');
        }

        return $response;
    }

    private function friendlySmtpError(string $message): string
    {
        if (str_contains($message, '535-5.7.8') || str_contains($message, 'BadCredentials')) {
            return 'Gmail menolak login. Pastikan smtp.username benar dan smtp.password adalah App Password 16 digit (bukan password login biasa). Buat App Password di myaccount.google.com/apppasswords.';
        }

        if (str_contains($message, 'STARTTLS')) {
            return 'Koneksi TLS ke Gmail gagal. Pastikan koneksi internet aktif dan extension OpenSSL PHP menyala.';
        }

        if (str_contains($message, 'Connection timed out')) {
            return 'Koneksi ke server SMTP timeout. Periksa firewall atau gunakan port 465 dengan encryption SSL.';
        }

        return $message;
    }
}