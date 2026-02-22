<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Frontend\ClientController as FrontendClientController;
use App\Http\Controllers\Frontend\TaskController as FrontendTaskController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Frontend\ProductController as FrontendProductController;
use App\Http\Controllers\Cabinet\ClientAuthController;
use App\Http\Controllers\Cabinet\CabinetController;
use App\Http\Controllers\LandingController;
use App\Support\PwaIconGenerator;
use Illuminate\Support\Facades\Route;

// Статика изображений (если сервер отдаёт всё в Laravel)
Route::get('/images/logo.png', function () {
    $path = public_path('images/logo.png');
    if (!is_file($path)) abort(404);
    return response()->file($path, ['Content-Type' => 'image/png']);
})->name('logo');
Route::get('/images/hero-banner.png', function () {
    $path = public_path('images/hero-banner.png');
    if (!is_file($path)) abort(404);
    return response()->file($path, ['Content-Type' => 'image/png']);
})->name('hero.banner');
Route::get('/manifest.json', function () {
    $path = public_path('manifest.json');
    if (!is_file($path)) abort(404);
    return response()->file($path, ['Content-Type' => 'application/manifest+json']);
})->name('manifest');
Route::get('/sw.js', function () {
    $path = public_path('sw.js');
    if (!is_file($path)) abort(404);
    return response()->file($path, ['Content-Type' => 'application/javascript']);
})->name('sw');
Route::get('/images/pwa-icon-192.png', function () {
    $path = PwaIconGenerator::ensureIcon(192);
    if (!$path) {
        $path = public_path('images/logo.png');
        if (!is_file($path)) abort(404);
    }
    return response()->file($path, ['Content-Type' => 'image/png']);
});
Route::get('/images/pwa-icon-512.png', function () {
    $path = PwaIconGenerator::ensureIcon(512);
    if (!$path) {
        $path = public_path('images/logo.png');
        if (!is_file($path)) abort(404);
    }
    return response()->file($path, ['Content-Type' => 'image/png']);
});

// Главная — лендинг ломбарда
Route::get('/', [LandingController::class, 'lombard'])->name('home');

// Стартовая страница компании/CRM (строящиеся объекты) — по желанию
Route::get('/company', [LandingController::class, 'index'])->name('landing.company');
Route::get('/object/{project}', [LandingController::class, 'project'])->name('landing.project');

// Личный кабинет: страница входа (форма входа или редирект в кабинет, если уже залогинен)
Route::get('/cabinet', function () {
    if (session('client_id')) {
        return redirect()->route('cabinet.dashboard');
    }
    return app(ClientAuthController::class)->showLogin();
})->name('cabinet.login');
Route::post('/cabinet/auth/password', [ClientAuthController::class, 'handlePasswordLogin'])->name('cabinet.password.login');
Route::get('/cabinet/auth/telegram', [ClientAuthController::class, 'handleTelegramCallback'])->name('cabinet.telegram.callback');
Route::post('/cabinet/logout', [ClientAuthController::class, 'logout'])->name('cabinet.logout');
Route::get('/cabinet/logout', [ClientAuthController::class, 'logout'])->name('cabinet.logout.get');

// Подписка на календарь задач (доступ по токену, без сессии — для iPhone)
Route::get('/cabinet/calendar/feed.ics', [App\Http\Controllers\Cabinet\CalendarFeedController::class, 'feed'])->name('cabinet.calendar.feed');
Route::get('/cabinet/calendar/feed', [App\Http\Controllers\Cabinet\CalendarFeedController::class, 'feed'])->name('cabinet.calendar.feed.alt');

// Личный кабинет: после входа (вкладки — транзакции, канбан, профиль)
Route::middleware('client')->prefix('cabinet')->name('cabinet.')->group(function () {
    Route::get('/dashboard', [CabinetController::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [CabinetController::class, 'transactions'])->name('transactions');
    Route::get('/board', [CabinetController::class, 'board'])->name('board');
    Route::get('/projects', [CabinetController::class, 'projectsIndex'])->name('projects.index');
    Route::get('/projects/{project}', [CabinetController::class, 'projectShow'])->name('projects.show');
    Route::get('/projects/{project}/stages/{stage}', [CabinetController::class, 'stageDetail'])->name('projects.stages.show');
    Route::patch('/projects/{project}/stages/{stage}', [CabinetController::class, 'updateStageStatus'])->name('projects.stages.updateStatus');
    Route::post('/projects/{project}/stages/{stage}/photos', [CabinetController::class, 'uploadStagePhoto'])->name('projects.stages.photos.store');
    Route::post('/projects/{project}/stages/{stage}/comments', [CabinetController::class, 'storeStageComment'])->name('projects.stages.comments.store');
    Route::post('/projects/{project}/investments', [CabinetController::class, 'storeInvestment'])->name('projects.investments.store');
    Route::delete('/projects/{project}/investments/{investment}', [CabinetController::class, 'destroyInvestment'])->name('projects.investments.destroy');
    Route::get('/projects/{project}/apartments/create', [CabinetController::class, 'createApartment'])->name('projects.apartments.create');
    Route::post('/projects/{project}/apartments', [CabinetController::class, 'storeApartment'])->name('projects.apartments.store');
    Route::get('/projects/{project}/apartments/{apartment}', [CabinetController::class, 'apartmentShow'])->name('projects.apartments.show');
    Route::get('/projects/{project}/apartments/{apartment}/edit', [CabinetController::class, 'editApartment'])->name('projects.apartments.edit');
    Route::put('/projects/{project}/apartments/{apartment}', [CabinetController::class, 'updateApartment'])->name('projects.apartments.update');
    Route::delete('/projects/{project}/apartments/{apartment}', [CabinetController::class, 'destroyApartment'])->name('projects.apartments.destroy');
    Route::post('/projects/{project}/apartments/{apartment}/layout-photo', [CabinetController::class, 'uploadApartmentLayoutPhoto'])->name('projects.apartments.layout-photo');
    Route::post('/projects/{project}/document-fields', [CabinetController::class, 'storeDocumentField'])->name('projects.document-fields.store');
    Route::delete('/projects/{project}/document-fields/{documentField}', [CabinetController::class, 'destroyDocumentField'])->name('projects.document-fields.destroy');
    Route::post('/projects/{project}/documents', [CabinetController::class, 'storeDocument'])->name('projects.documents.store');
    Route::put('/projects/{project}/documents/{document}', [CabinetController::class, 'updateDocument'])->name('projects.documents.update');
    Route::delete('/projects/{project}/documents/{document}', [CabinetController::class, 'destroyDocument'])->name('projects.documents.destroy');
    Route::get('/tasks/create', [CabinetController::class, 'createTask'])->name('tasks.create');
    Route::post('/tasks', [CabinetController::class, 'storeTask'])->name('tasks.store');
    Route::patch('/tasks/{task}/status', [CabinetController::class, 'updateTaskStatus'])->name('tasks.updateStatus');
    Route::get('/video', [CabinetController::class, 'videoConference'])->name('video');
    Route::get('/profile', [CabinetController::class, 'profile'])->name('profile');
    Route::post('/profile/password', [CabinetController::class, 'updatePassword'])->name('profile.password');
    Route::get('/calendar', [CabinetController::class, 'calendarSync'])->name('calendar');
    Route::post('/push-subscribe', [CabinetController::class, 'pushSubscribe'])->name('push.subscribe');
});

// Публичный фронтенд (каталог клиентов, товаров — без входа, по желанию можно убрать)
Route::get('/clients', [FrontendClientController::class, 'index'])->name('frontend.clients.list');
Route::get('/clients/{client}', [FrontendClientController::class, 'show'])->name('frontend.clients.show');
Route::get('/products', [FrontendProductController::class, 'index'])->name('frontend.products.index');
Route::get('/products/{product}', [FrontendProductController::class, 'show'])->name('frontend.products.show');
Route::get('/tasks', [FrontendTaskController::class, 'board'])->name('frontend.tasks.board');
Route::patch('/tasks/{task}/status', [FrontendTaskController::class, 'updateStatus'])->name('frontend.tasks.updateStatus');

// Админ: авторизация
Route::middleware('guest')->group(function () {
    Route::get('admin/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('admin/login', [LoginController::class, 'login']);
});

Route::match(['get', 'post'], 'admin/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Админ-панель
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', AdminClientController::class);
    Route::get('clients/{client}/balance', function (App\Models\Client $client) { return redirect()->route('admin.clients.show', $client); })->name('clients.balance.get');
    Route::post('clients/{client}/balance', [AdminClientController::class, 'balance'])->name('clients.balance');
    Route::post('clients/{client}/photo', [AdminClientController::class, 'uploadPhoto'])->name('clients.photo');
    Route::delete('clients/{client}/photo', [AdminClientController::class, 'deletePhoto'])->name('clients.photo.delete');

    Route::resource('custom-fields', CustomFieldController::class)->except(['show']);
    Route::post('custom-fields/reorder', [CustomFieldController::class, 'reorder'])->name('custom-fields.reorder');

    Route::resource('users', UserController::class)->except(['show']);
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('tasks/board', [AdminTaskController::class, 'board'])->name('tasks.board');
    Route::resource('tasks', AdminTaskController::class)->except(['show']);
    Route::get('tasks/{task}', function (App\Models\Task $task) { return redirect()->route('admin.tasks.edit', $task); })->name('tasks.show');
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::get('products/{product}', function (App\Models\Product $product) { return redirect()->route('admin.products.edit', $product); })->name('products.show');
    Route::resource('projects', AdminProjectController::class);
    Route::post('projects/{project}/expense-items', [AdminProjectController::class, 'storeExpenseItem'])->name('projects.expense-items.store');
    Route::delete('projects/{project}/expense-items/{expenseItem}', [AdminProjectController::class, 'destroyExpenseItem'])->name('projects.expense-items.destroy');
    Route::post('projects/{project}/construction-stages', [AdminProjectController::class, 'storeConstructionStage'])->name('projects.construction-stages.store');
    Route::get('projects/{project}/construction-stages/{constructionStage}/edit', [AdminProjectController::class, 'editConstructionStage'])->name('projects.construction-stages.edit');
    Route::put('projects/{project}/construction-stages/{constructionStage}', [AdminProjectController::class, 'updateConstructionStage'])->name('projects.construction-stages.update');
    Route::delete('projects/{project}/construction-stages/{constructionStage}', [AdminProjectController::class, 'destroyConstructionStage'])->name('projects.construction-stages.destroy');
    Route::post('projects/{project}/construction-stages/{constructionStage}/works', [AdminProjectController::class, 'storeConstructionStageWork'])->name('projects.construction-stages.works.store');
    Route::delete('projects/{project}/construction-stages/{constructionStage}/works/{construction_stage_work}', [AdminProjectController::class, 'destroyConstructionStageWork'])->name('projects.construction-stages.works.destroy');
    Route::get('projects/{project}/apartments/create', [AdminProjectController::class, 'createApartment'])->name('projects.apartments.create');
    Route::post('projects/{project}/apartments', [AdminProjectController::class, 'storeApartment'])->name('projects.apartments.store');
    Route::get('projects/{project}/apartments/{apartment}', [AdminProjectController::class, 'apartmentShow'])->name('projects.apartments.show');
    Route::get('projects/{project}/apartments/{apartment}/edit', [AdminProjectController::class, 'editApartment'])->name('projects.apartments.edit');
    Route::put('projects/{project}/apartments/{apartment}', [AdminProjectController::class, 'updateApartment'])->name('projects.apartments.update');
    Route::delete('projects/{project}/apartments/{apartment}', [AdminProjectController::class, 'destroyApartment'])->name('projects.apartments.destroy');
    Route::post('projects/{project}/apartments/{apartment}/layout-photo', [AdminProjectController::class, 'uploadApartmentLayoutPhoto'])->name('projects.apartments.layout-photo');
    Route::post('projects/{project}/site-settings', [AdminProjectController::class, 'updateSiteSettings'])->name('projects.site-settings');
    Route::post('projects/{project}/site-photos', [AdminProjectController::class, 'uploadSitePhoto'])->name('projects.site-photos.store');
    Route::delete('projects/{project}/site-photos/{photo}', [AdminProjectController::class, 'destroySitePhoto'])->name('projects.site-photos.destroy');
    Route::post('products/{product}/photo', [AdminProductController::class, 'uploadPhoto'])->name('products.photo');
    Route::delete('products/{product}/photo', [AdminProductController::class, 'deletePhoto'])->name('products.photo.delete');
    Route::get('activity', [DashboardController::class, 'activity'])->name('activity');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
});
