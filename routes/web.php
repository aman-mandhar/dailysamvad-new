<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DateArchiveController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndexNowKeyController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemaps/posts-{page}.xml', [SitemapController::class, 'posts'])->whereNumber('page')->name('seo.sitemap.posts');
Route::get('/sitemaps/categories.xml', [SitemapController::class, 'categories'])->name('seo.sitemap.categories');
Route::get('/sitemaps/tags.xml', [SitemapController::class, 'tags'])->name('seo.sitemap.tags');
Route::get('/sitemaps/authors.xml', [SitemapController::class, 'authors'])->name('seo.sitemap.authors');
Route::get('/sitemaps/pages.xml', [SitemapController::class, 'pages'])->name('seo.sitemap.pages');
Route::get('/news-sitemap.xml', [SitemapController::class, 'news'])->name('seo.sitemap.news');
Route::get('/news-sitemaps/news-{page}.xml', [SitemapController::class, 'newsChunk'])->whereNumber('page')->name('seo.sitemap.news.chunk');
Route::get('/image-sitemap.xml', [SitemapController::class, 'images'])->name('seo.sitemap.images');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/{key}.txt', IndexNowKeyController::class)->where('key', '[A-Za-z0-9-]{8,128}')->name('seo.indexnow.key');
Route::get('/news/{slug}', [PostController::class, 'legacy'])->name('news.legacy');
Route::get('/{year}/{month}/{slug}', [PostController::class, 'show'])
    ->where([
        'year' => '[0-9]{4}',
        'month' => '0[1-9]|1[0-2]',
    ])
    ->name('news.show');
Route::get('/category/{slug}', CategoryController::class)->name('categories.show');
Route::get('/tag/{slug}', TagController::class)->name('tags.show');
Route::get('/author/{username}', AuthorController::class)->name('authors.show');
Route::get('/search', SearchController::class)->name('search');
Route::get('/archive/{year}', [DateArchiveController::class, 'year'])->whereNumber('year')->name('archives.year');
Route::get('/archive/{year}/{month}', [DateArchiveController::class, 'month'])->whereNumber(['year', 'month'])->name('archives.month');
Route::get('/archive/{year}/{month}/{day}', [DateArchiveController::class, 'day'])->whereNumber(['year', 'month', 'day'])->name('archives.day');
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

Route::get('/{slug}', [PostController::class, 'legacy'])->name('news.legacy-root');
