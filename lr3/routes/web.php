<?php

use App\Http\Controllers\ItemController;

Route::get('/', function () {
    return redirect()->route('items.index');
});

Route::resource('items', ItemController::class);


Route::get('/test-image', function () {
    return class_exists(Image::class)
        ? 'OK'
        : 'NO';
});
