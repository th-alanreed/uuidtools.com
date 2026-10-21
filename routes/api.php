<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::controller(ApiController::class)->group(function () {
    Route::get('generate/{version}/namespace/{namespace}/name/{name}', 'nameBasedPath')->where('name', '.*');
    Route::get('generate/{version}/count/{count}', 'generate');
    Route::get('generate/{version}', 'generate');
    Route::get('decode/{uuid}', 'decode');
    Route::get('validate/{uuid}', 'validate');
});
