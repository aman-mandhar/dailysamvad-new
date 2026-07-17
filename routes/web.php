<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DateArchiveController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/news/{slug}', [PostController::class, 'show'])->name('news.show');
Route::get('/category/{slug}', CategoryController::class)->name('categories.show');
Route::get('/tag/{slug}', TagController::class)->name('tags.show');
Route::get('/author/{username}', AuthorController::class)->name('authors.show');
Route::get('/search', SearchController::class)->name('search');
Route::get('/archive/{year}', [DateArchiveController::class, 'year'])->whereNumber('year')->name('archives.year');
Route::get('/archive/{year}/{month}', [DateArchiveController::class, 'month'])->whereNumber(['year', 'month'])->name('archives.month');
Route::get('/archive/{year}/{month}/{day}', [DateArchiveController::class, 'day'])->whereNumber(['year', 'month', 'day'])->name('archives.day');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/feed.xml', FeedController::class)->name('feed');

if (app()->environment(['local', 'development'])) {
    Route::view('/frontend/foundation-preview', 'frontend.foundation-preview')
        ->name('frontend.foundation-preview');
}

foreach (config('static-pages') as $page) {
    Route::get('/'.$page['slug'], StaticPageController::class)
        ->defaults('slug', $page['slug'])
        ->name($page['route']);
}
