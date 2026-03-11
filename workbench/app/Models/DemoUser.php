<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoUser extends Model
{
    protected $table    = 'demo_users';
    protected $fillable = ['imie', 'nazwisko', 'adres', 'status'];
}