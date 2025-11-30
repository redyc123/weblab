<?php

use App\Http\Controllers\ItemController;

Route::get('/', function () {
    return redirect()->route('items.index');
});

Route::resource('items', ItemController::class);
