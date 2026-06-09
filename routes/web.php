<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperTaskController;
use App\Http\Controllers\LeadAssetController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes (unauthenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Lead management (Super Admin, Leads Admin, Sales)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,leads_admin,sales')->group(function () {
        // Bulk delete — declared before the resource so the static path wins.
        Route::delete('/leads/bulk-destroy', [LeadController::class, 'bulkDestroy'])
            ->name('leads.bulk-destroy');

        Route::resource('leads', LeadController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);

        // Assign a developer to a lead.
        Route::post('/leads/{lead}/developer-task', [DeveloperTaskController::class, 'store'])
            ->name('leads.developer-task.store');
    });

    /*
    |----------------------------------------------------------------------
    | Lead list + details + workflow (all roles; developers see only
    | assigned leads — enforced via Lead::scopeVisibleTo and show()).
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,leads_admin,sales,developer')->group(function () {
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');

        // Details page — developer access is restricted to assigned leads inside show().
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');

        // Asset upload (developers included, per the form request authorization).
        Route::post('/leads/{lead}/assets', [LeadAssetController::class, 'store'])
            ->name('leads.assets.store');

        // Asset download / delete.
        Route::get('/assets/{asset}/download', [LeadAssetController::class, 'download'])
            ->name('assets.download');
        Route::delete('/assets/{asset}', [LeadAssetController::class, 'destroy'])
            ->name('assets.destroy');

        // Developer workflow update (authorization enforced in the form request).
        Route::put('/developer-tasks/{developerTask}', [DeveloperTaskController::class, 'update'])
            ->name('developer-tasks.update');
    });
});

/*
|--------------------------------------------------------------------------
| Root redirect
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});
