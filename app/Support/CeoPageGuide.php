<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class CeoPageGuide
{
    public static function forCurrentRoute(): ?array
    {
        $name = Route::currentRouteName();
        if (! $name) {
            return null;
        }

        $pages = config('ceo_guide.pages', []);
        if (! isset($pages[$name])) {
            return null;
        }

        $page = $pages[$name];
        $shared = config('ceo_guide.shared_glossary', []);
        $extra = $page['glossary'] ?? [];

        return [
            'id' => $page['id'] ?? str_replace('.', '-', $name),
            'route' => $name,
            'icon' => $page['icon'] ?? 'fa-circle-info',
            'title' => $page['title'] ?? 'Halaman',
            'subtitle' => $page['subtitle'] ?? '',
            'action' => $page['action'] ?? null,
            'glossary' => array_merge($shared, $extra),
            'formulas' => array_merge(
                config('ceo_guide.shared_formulas', []),
                $page['formulas'] ?? []
            ),
        ];
    }
}
