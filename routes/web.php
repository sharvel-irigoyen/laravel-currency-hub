<?php

use Illuminate\Support\Facades\Route;

// No web routes for this API-only application.
Route::get('/', function () {
    abort(404);
});
