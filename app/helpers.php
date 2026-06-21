<?php

if (!function_exists('hub_rp')) {
    function hub_rp($amount, bool $signed = false): string
    {
        $n = (float) $amount;
        $prefix = $signed && $n < 0 ? '-' : '';
        return $prefix . 'Rp ' . number_format(abs($n), 0, ',', '.');
    }
}

if (!function_exists('hub_pct')) {
    function hub_pct(?float $ratio, int $decimals = 1): string
    {
        if ($ratio === null) {
            return '—';
        }
        return number_format($ratio * 100, $decimals, ',', '.') . '%';
    }
}

if (!function_exists('hub_num')) {
    function hub_num($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('hub_export_page_actions')) {
    /**
     * @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    function hub_export_page_actions(string $type, array $query = []): array
    {
        $base = array_merge($query, []);

        return [
            [
                'label' => 'Export Excel',
                'url' => route('monitoring.export', array_merge($base, ['type' => $type, 'format' => 'xlsx'])),
                'icon' => 'fa-file-excel',
                'variant' => 'outline',
                'download' => true,
            ],
            [
                'label' => 'Export PDF',
                'url' => route('monitoring.export', array_merge($base, ['type' => $type, 'format' => 'pdf'])),
                'icon' => 'fa-file-pdf',
                'variant' => 'outline',
                'download' => true,
            ],
        ];
    }
}
