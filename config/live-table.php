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
];
