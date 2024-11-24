<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Nyt\BestSellersController;


Route::group(['prefix' => '1/nyt' ], function () {
        Route::get('best-sellers', [BestSellersController::class,'index'])->name('api.v1.nyt.best-sellers.index');
});