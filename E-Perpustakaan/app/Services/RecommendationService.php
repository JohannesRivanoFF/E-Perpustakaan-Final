<?php
declare(strict_types=1);

require_once __DIR__ . '/../Support/HttpClient.php';

final class RecommendationService
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

    public function scoreBooks(array $books, array $context = []): array
    {
        if (!$this->isEnabled() || $books === []) {
            return $books;
        }

        try {
            $response = $this->http->postJson(
                (string) $this->config['endpoint'],
                [
                    'context' => $context,
                    'books' => array_map(static fn (array $book): array => [
                        'id_buku' => (int) $book['id_buku'],
                        'judul' => (string) $book['judul'],
                        'pengarang' => (string) ($book['pengarang'] ?? ''),
                        'kategori' => (string) ($book['kategori'] ?? ''),
                        'tahun_terbit' => (int) ($book['tahun_terbit'] ?? 0),
                        'rating' => (float) ($book['total_rating'] ?? 0),
                        'jumlah_rating' => (int) ($book['jumlah_rating'] ?? 0),
                        'stok' => (int) ($book['stok'] ?? 0),
                    ], $books),
                ],
                $this->authHeaders(),
                (int) ($this->config['timeout'] ?? 5)
            );

            $scores = $this->normalizeScores($response);
            foreach ($books as &$book) {
                $id = (int) $book['id_buku'];
                $book['recommendation_score'] = $scores[$id] ?? $this->fallbackScore($book);
            }
            unset($book);
        } catch (Throwable) {
            foreach ($books as &$book) {
                $book['recommendation_score'] = $this->fallbackScore($book);
            }
            unset($book);
        }

        return $books;
    }

    public function scoreSingleBook(array $book, array $context = []): float
    {
        $scored = $this->scoreBooks([$book], $context);

        return (float) ($scored[0]['recommendation_score'] ?? $this->fallbackScore($book));
    }

    private function authHeaders(): array
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');

        return $apiKey === '' ? [] : ['Authorization' => "Bearer {$apiKey}"];
    }

    private function normalizeScores(array $response): array
    {
        $scores = [];

        if (isset($response['scores']) && is_array($response['scores'])) {
            foreach ($response['scores'] as $id => $score) {
                $scores[(int) $id] = (float) $score;
            }
        }

        if (isset($response['recommendations']) && is_array($response['recommendations'])) {
            foreach ($response['recommendations'] as $item) {
                if (isset($item['id_buku'], $item['score'])) {
                    $scores[(int) $item['id_buku']] = (float) $item['score'];
                }
            }
        }

        return $scores;
    }

    private function fallbackScore(array $book): float
    {
        $rating = (float) ($book['total_rating'] ?? 0);
        $reviews = min((int) ($book['jumlah_rating'] ?? 0), 20) / 20;
        $stock = ((int) ($book['stok'] ?? 0)) > 0 ? 0.15 : 0.0;

        return round(min(5, $rating + $reviews + $stock), 2);
    }
}
