<?php

use App\Http\Controllers\Account\NotificationController as AccountNotificationController;
use App\Http\Controllers\Account\PreferenceController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ReadingHistoryController;
use App\Http\Controllers\Account\ReferralController;
use App\Http\Controllers\Account\SavedArticleController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AnalyticsBeaconController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
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
use App\Http\Middleware\PublicResponseCache;

Route::get('/', HomeController::class)->middleware(PublicResponseCache::class)->name('home');
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
Route::get('/{year}/{month}/{slug}', [PostController::class, 'show'])->middleware(PublicResponseCache::class)
    ->where([
        'year' => '[0-9]{4}',
        'month' => '0[1-9]|1[0-2]',
    ])
    ->name('news.show');
Route::get('/category/{slug}', CategoryController::class)->middleware(PublicResponseCache::class)->name('categories.show');
Route::get('/tag/{slug}', TagController::class)->middleware(PublicResponseCache::class)->name('tags.show');
Route::get('/author/{username}', AuthorController::class)->middleware(PublicResponseCache::class)->name('authors.show');
Route::get('/search', SearchController::class)->name('search');
Route::post('/analytics/beacon/{post}', AnalyticsBeaconController::class)->middleware('throttle:60,1')->name('analytics.beacon');
Route::get('/archive/{year}', [DateArchiveController::class, 'year'])->middleware(PublicResponseCache::class)->whereNumber('year')->name('archives.year');
Route::get('/archive/{year}/{month}', [DateArchiveController::class, 'month'])->middleware(PublicResponseCache::class)->whereNumber(['year', 'month'])->name('archives.month');
Route::get('/archive/{year}/{month}/{day}', [DateArchiveController::class, 'day'])->middleware(PublicResponseCache::class)->whereNumber(['year', 'month', 'day'])->name('archives.day');
Route::get('/feed.xml', FeedController::class)->middleware(PublicResponseCache::class)->name('feed');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->middleware('active')->name('dashboard');

    Route::prefix('account')->name('account.')->middleware('active')->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/security', [SecurityController::class, 'edit'])->name('security.edit');
        Route::put('/security/password', [SecurityController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
        Route::get('/referrals', ReferralController::class)->name('referrals');
        Route::get('/saved-articles', [SavedArticleController::class, 'index'])->name('saved.index');
        Route::post('/saved-articles/{post}', [SavedArticleController::class, 'store'])->name('saved.store');
        Route::delete('/saved-articles/{bookmark}', [SavedArticleController::class, 'destroy'])->name('saved.destroy');
        Route::get('/reading-history', ReadingHistoryController::class)->name('history');
        Route::get('/notifications', [AccountNotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [AccountNotificationController::class, 'read'])->name('notifications.read');
        Route::patch('/notifications/read-all', [AccountNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/preferences', [PreferenceController::class, 'edit'])->name('preferences.edit');
        Route::patch('/preferences', [PreferenceController::class, 'update'])->name('preferences.update');
    });
});

if (app()->environment(['local', 'development'])) {
    Route::view('/frontend/foundation-preview', 'frontend.foundation-preview')
        ->name('frontend.foundation-preview');
}

foreach (config('static-pages') as $page) {
    Route::get('/'.$page['slug'], StaticPageController::class)
        ->middleware(PublicResponseCache::class)
        ->defaults('slug', $page['slug'])
        ->name($page['route']);
}

Route::get('/{slug}', [PostController::class, 'legacy'])->name('news.legacy-root');
