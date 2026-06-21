<?php

namespace App\Support;

use App\Services\Ceo\CeoChatbotSnapshotService;
use Illuminate\Support\Facades\Route;

class CeoChatbot
{
    /** FAQ ids that get live snapshot prepended */
    private const SNAPSHOT_FAQ_IDS = [
        'ceo-harian',
        'toko-rugi',
        'skor-kesehatan',
        'urgent-aksi',
        'scale-iklan',
    ];

    public static function bootstrap(): array
    {
        $route = Route::currentRouteName();
        $cfg = config('ceo_chatbot', []);
        $faqs = self::sortedFaqs($cfg['faqs'] ?? [], $route);

        $snapshot = app(CeoChatbotSnapshotService::class)->get();

        return [
            'bot' => $cfg['bot'] ?? [],
            'personality' => $cfg['personality'] ?? [],
            'quick_starters' => self::startersForRoute($cfg, $route),
            'faqs' => self::mapFaqs($faqs),
            'snapshot' => $snapshot,
            'current_route' => $route,
            'endpoints' => [
                'ask' => route('monitoring.chatbot.ask'),
            ],
        ];
    }

    public static function answer(string $question): array
    {
        $cfg = config('ceo_chatbot', []);
        $personality = $cfg['personality'] ?? [];
        $faqs = $cfg['faqs'] ?? [];
        $faq = self::findFaq($question, $faqs);
        $snapshot = app(CeoChatbotSnapshotService::class)->get();

        if ($faq) {
            $answer = trim($faq['answer'] ?? '');
            if (in_array($faq['id'] ?? '', self::SNAPSHOT_FAQ_IDS, true)) {
                $block = app(CeoChatbotSnapshotService::class)->contextBlock($snapshot);
                $answer = $block . "\n\n---\n\n" . $answer;
            }

            $mapped = self::mapFaq($faq);

            return [
                'ok' => true,
                'matched' => true,
                'ack' => self::pick($personality['acknowledgments'] ?? [], 'Baik, ini penjelasannya ya 👇'),
                'thinking' => self::pick($personality['thinking'] ?? [], 'Sedang menyiapkan jawaban…'),
                'question' => $faq['question'] ?? $question,
                'answer' => $answer,
                'link' => $mapped['link'] ?? null,
                'follow_ups' => $faq['follow_ups'] ?? self::defaultFollowUps($faq['id'] ?? '', $faqs),
            ];
        }

        return [
            'ok' => true,
            'matched' => false,
            'ack' => self::pick($personality['empathy'] ?? [], 'Hmm, belum ketemu jawaban pas untuk itu.'),
            'thinking' => self::pick($personality['thinking'] ?? [], 'Sedang mencari…'),
            'answer' => self::pick($personality['fallbacks'] ?? [], $cfg['bot']['fallback'] ?? 'Coba pilih pertanyaan populer di bawah ya.'),
            'suggestions' => collect($faqs)->take(4)->pluck('question')->all(),
            'snapshot_hint' => self::snapshotHint($snapshot),
        ];
    }

    /** @deprecated Use bootstrap() via API — kept for blade fallback */
    public static function payload(): array
    {
        return self::bootstrap();
    }

    private static function sortedFaqs(array $faqs, ?string $route): \Illuminate\Support\Collection
    {
        return collect($faqs)->sortByDesc(function ($faq) use ($route) {
            $routes = $faq['routes'] ?? [];
            if ($route && in_array($route, $routes, true)) {
                return 100;
            }

            return 0;
        })->values();
    }

    private static function startersForRoute(array $cfg, ?string $route): array
    {
        $starters = $cfg['quick_starters'] ?? [];
        $contextual = collect($cfg['faqs'] ?? [])
            ->filter(fn ($f) => $route && in_array($route, $f['routes'] ?? [], true))
            ->take(2)
            ->pluck('question')
            ->all();

        return array_values(array_unique(array_merge($contextual, $starters)));
    }

    private static function mapFaqs($faqs): array
    {
        return collect($faqs)->map(fn ($faq) => self::mapFaq($faq))->all();
    }

    private static function mapFaq(array $faq): array
    {
        $out = [
            'id' => $faq['id'] ?? '',
            'question' => $faq['question'] ?? '',
            'answer' => trim($faq['answer'] ?? ''),
            'keywords' => $faq['keywords'] ?? [],
        ];

        if (! empty($faq['link']['route'])) {
            try {
                $out['link'] = [
                    'label' => $faq['link']['label'] ?? 'Buka halaman',
                    'url' => route($faq['link']['route']),
                ];
            } catch (\Throwable) {
                // route may not exist
            }
        }

        return $out;
    }

    public static function findFaq(string $query, array $faqs): ?array
    {
        $q = self::normalize($query);
        if ($q === '') {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($faqs as $faq) {
            $score = self::scoreFaq($q, $faq);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $faq;
            }
        }

        return $bestScore >= 6 ? $best : null;
    }

    private static function scoreFaq(string $q, array $faq): int
    {
        $score = 0;
        $question = self::normalize($faq['question'] ?? '');

        if ($question === $q || str_contains($question, $q) || str_contains($q, substr($question, 0, 12))) {
            $score += 50;
        }

        foreach ($faq['keywords'] ?? [] as $kw) {
            $k = self::normalize($kw);
            if ($k === '') {
                continue;
            }
            if (str_contains($q, $k)) {
                $score += count(explode(' ', $k)) * 12;
            }
            if (str_contains($k, $q) && strlen($q) > 3) {
                $score += 8;
            }
        }

        foreach (explode(' ', $q) as $word) {
            if (strlen($word) < 3) {
                continue;
            }
            if (str_contains($question, $word)) {
                $score += 3;
            }
            foreach ($faq['keywords'] ?? [] as $kw) {
                if (str_contains(self::normalize($kw), $word)) {
                    $score += 4;
                }
            }
        }

        return $score;
    }

    private static function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s) ?? $s;

        return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
    }

    private static function pick(array $items, string $fallback): string
    {
        if ($items === []) {
            return $fallback;
        }

        return $items[array_rand($items)];
    }

    private static function defaultFollowUps(string $id, array $faqs): array
    {
        $related = [
            'ceo-harian' => ['laba-bersih', 'urgent-aksi'],
            'laba-bersih' => ['margin-kotor-net', 'toko-rugi'],
            'hpp-cogs' => ['bleeder', 'roas-set'],
            'roas-set' => ['roas-bisnis', 'scale-iklan'],
            'toko-rugi' => ['bleeder', 'scale-iklan'],
        ];

        $ids = $related[$id] ?? [];
        $questions = [];
        foreach ($faqs as $faq) {
            if (in_array($faq['id'] ?? '', $ids, true)) {
                $questions[] = $faq['question'];
            }
        }

        return array_slice($questions, 0, 2);
    }

    private static function snapshotHint(array $snapshot): ?string
    {
        if (($snapshot['urgent_count'] ?? 0) > 0) {
            return '💡 Tip: Ada ' . $snapshot['urgent_count'] . ' urgent di Pusat Aksi — coba tanya "Apa arti urgent di Pusat Aksi?"';
        }
        if (! ($snapshot['hpp_ok'] ?? true)) {
            return '💡 Tip: HPP baru ' . ($snapshot['hpp_pct'] ?? 0) . '% — coba tanya "Kenapa HPP wajib diisi?"';
        }

        return null;
    }
}
