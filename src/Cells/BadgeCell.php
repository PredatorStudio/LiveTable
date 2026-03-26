<?php

namespace PredatorStudio\LiveTable\Cells;

class BadgeCell extends Cell
{
    /**
     * Semantic color → Tailwind classes mapping.
     * Full class names so Tailwind scanner can detect them.
     */
    private const TAILWIND = [
        'success' => 'bg-green-100 text-green-800 ring-green-600/20',
        'danger' => 'bg-red-100 text-red-800 ring-red-600/20',
        'warning' => 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
        'info' => 'bg-blue-100 text-blue-800 ring-blue-600/20',
        'primary' => 'bg-indigo-100 text-indigo-800 ring-indigo-600/20',
        'secondary' => 'bg-gray-100 text-gray-800 ring-gray-600/20',
    ];

    /**
     * Map: value => 'bootstrap-color'
     *   or value => ['color' => 'success', 'label' => 'Aktywny']
     */
    public function __construct(
        private readonly array $map = [],
    ) {}

    public function render(mixed $row, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $this->renderEmpty();
        }

        $str = (string) $value;
        $entry = $this->map[$str] ?? null;

        if ($entry === null) {
            return $this->badge('secondary', e($str));
        }

        if (is_array($entry)) {
            $color = $entry['color'] ?? 'secondary';
            $label = e($entry['label'] ?? $str);
        } else {
            $color = (string) $entry;
            $label = e($str);
        }

        return $this->badge($color, $label);
    }

    private function badge(string $color, string $label): string
    {
        if (config('live-table.theme', 'bootstrap') === 'tailwind') {
            $classes = self::TAILWIND[$color] ?? self::TAILWIND['secondary'];

            return "<span class=\"inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {$classes}\">{$label}</span>";
        }

        return "<span class=\"badge bg-{$color}\">{$label}</span>";
    }
}
