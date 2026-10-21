<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/generate', fn () => redirect('/', 301));
Route::get('/generate/{slug}', [PageController::class, 'generate'])->name('generate');
Route::get('/decode/{uuid?}', [PageController::class, 'decode'])->name('decode');

Route::view('/docs', 'docs')->name('docs');
Route::view('/what-is-uuid', 'what-is-uuid')->name('what-is-uuid');
Route::view('/uuid-versions-explained', 'versions-explained')->name('versions-explained');
Route::view('/terms', 'terms')->name('terms');
Route::view('/contact', 'contact')->name('contact');
Route::get('/sitemap', [PageController::class, 'sitemap']);
Route::get('/sitemap.xml', [PageController::class, 'sitemapXml']);

// Legacy URLs from the previous version of the site.
Route::get('/generator/{anything?}', fn (?string $anything = null) => redirect('/generate'.($anything ? '/'.$anything : ''), 301));
Route::get('/{slug}', fn (string $slug) => redirect('/generate/'.$slug, 301))
    ->whereIn('slug', ['v1', 'v2', 'v3', 'v4', 'v5', 'v6', 'v7', 'v8', 'timestamp-first', 'minecraft']);
