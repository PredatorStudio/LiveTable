<?php

use Illuminate\Support\Facades\Route;

Route::get('/demo', function () {
    $theme = config('live-table.theme', 'bootstrap');
    return view($theme === 'tailwind' ? 'demo-tailwind' : 'demo-bootstrap');
});

Route::get('/demo/bootstrap', fn () => redirect('http://localhost:8002/demo'));
Route::get('/demo/tailwind', fn () => redirect('http://localhost:8003/demo'));