<?php

use CharlesHarmon\QboAuth\Http\Controllers\QuickBooksOAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('qbo-auth.route_middleware', ['web']))
    ->prefix(config('qbo-auth.route_prefix', 'oauth/quickbooks'))
    ->name('qbo-auth.')
    ->group(function (): void {
        Route::get('/', [QuickBooksOAuthController::class, 'index'])->name('index');
        Route::get('connect', [QuickBooksOAuthController::class, 'connect'])->name('connect');
        Route::get('callback', [QuickBooksOAuthController::class, 'callback'])->name('callback');
        Route::get('connections/{connection}/company', [QuickBooksOAuthController::class, 'company'])->name('connections.company');
        Route::delete('connections/{connection}', [QuickBooksOAuthController::class, 'destroy'])->name('connections.destroy');
    });
