<?php

namespace PredatorStudio\LiveTable\Models;

use Illuminate\Database\Eloquent\Model;

class TableState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'state' => 'array',
    ];

    public function getTable(): string
    {
        return config('live-table.persist_state_table', 'live_table_states');
    }
}
