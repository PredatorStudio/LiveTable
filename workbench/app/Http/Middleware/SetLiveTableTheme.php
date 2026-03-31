<?php

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLiveTableTheme
{
    private const VIEWS_BASE = __DIR__.'/../../../../resources/views/';

    public function handle(Request $request, Closure $next): Response
    {
        $theme = $this->detectTheme($request);

        View::replaceNamespace('live-table', [self::VIEWS_BASE.$theme]);

        return $next($request);
    }

    private function detectTheme(Request $request): string
    {
        // Direct demo page request – read from URL.
        $path = $request->path();
        if (str_contains($path, 'demo/tailwind')) {
            return 'tailwind';
        }
        if (str_contains($path, 'demo/bootstrap')) {
            return 'bootstrap';
        }

        // Livewire AJAX request – read originating path from snapshot memo.
        if ($request->isJson() || $request->ajax()) {
            $components = $request->input('components', []);
            foreach ($components as $component) {
                $memo = $component['snapshot']['memo'] ?? $component['memo'] ?? [];
                $originPath = $memo['path'] ?? '';
                if (str_contains($originPath, 'tailwind')) {
                    return 'tailwind';
                }
                if (str_contains($originPath, 'bootstrap')) {
                    return 'bootstrap';
                }
            }
        }

        // Fallback to cookie.
        $theme = $request->cookie('live_table_theme', 'bootstrap');

        return in_array($theme, ['bootstrap', 'tailwind']) ? $theme : 'bootstrap';
    }
}