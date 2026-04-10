<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoProduct extends Model
{
    protected $table = 'demo_products';

    protected $fillable = ['name', 'category', 'price', 'quantity', 'is_active', 'manufactured_at'];

    protected $casts = [
        'is_active'       => 'boolean',
        'manufactured_at' => 'date:Y-m-d',
        'price'           => 'decimal:2',
        'quantity'        => 'integer',
    ];
}
