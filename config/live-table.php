<?php

return [
    /*
     * CSS framework used for table views.
     * Supported: 'bootstrap', 'tailwind'
     */
    'theme' => 'bootstrap',

    /*
     * Default row selection mode when $selectable = true.
     * 'checkbox' – dedicated checkbox column (default)
     * 'row'      – clicking anywhere on the row toggles selection
     * Can be overridden per table via: protected string $selectMode = 'row';
     */
    'select_mode' => 'checkbox',

    /*
     * Database table used to persist per-user table state (search, filters,
     * column order/visibility, sort, per-page).
     *
     * State persistence is DISABLED by default. Enable it per table by setting:
     *   public bool $persistState = true;
     *
     * Requires running the live-table migration:
     *   php artisan vendor:publish --tag=live-table-migrations
     *   php artisan migrate
     *
     * Set to null or leave the migration unpublished to skip DB entirely.
     */
    'persist_state_table' => 'live_table_states',

    /*
     * Custom field-type mappings for the default-creating modal.
     * Keys are exact column names or wildcard patterns (matched via Str::is).
     * Values are HTML input types (text, number, email, password, url, tel, color, …).
     * These have the highest priority and override built-in heuristics.
     *
     * Example:
     *   'phone'    => 'tel',
     *   '*_color'  => 'color',
     *   'website'  => 'url',
     */
    'creating_field_types' => [],
];
