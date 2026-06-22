<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeveloperTaskController;
use App\Http\Controllers\LeadAssetController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadWorkflowController;
use App\Http\Controllers\UserController;
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
    | Phase 6 analytics (all roles; data is scoped per role inside the
    | controller — developers/sales see only their own figures).
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,leads_admin,sales,developer')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

    // Team performance page — Super Admin & Leads Manager only.
    Route::middleware('role:super_admin,leads_admin')->group(function () {
        Route::get('/analytics/team', [AnalyticsController::class, 'team'])->name('analytics.team');
    });

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
    });

    // Developer assignment is admin-only (Super Admin, Leads Manager) — Sales
    // must never assign developers. Enforced here AND in StoreDeveloperTaskRequest.
    Route::middleware('role:super_admin,leads_admin')->group(function () {
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

    /*
    |----------------------------------------------------------------------
    | Phase 5 demo workflow transitions (fine-grained authorization lives
    | in the form requests; middleware provides a coarse role gate).
    |----------------------------------------------------------------------
    */
    // Assign developer (Super Admin, Leads Admin).
    Route::middleware('role:super_admin,leads_admin')->group(function () {
        Route::post('/leads/{lead}/assign', [LeadWorkflowController::class, 'assign'])
            ->name('leads.assign');
    });

    // Developer demo update (Super Admin, Leads Manager, assigned Developer).
    Route::middleware('role:super_admin,leads_admin,developer')->group(function () {
        Route::put('/leads/{lead}/demo', [LeadWorkflowController::class, 'demoUpdate'])
            ->name('leads.demo.update');
    });

    // Sales / follow-up update (Super Admin, Leads Admin, Sales).
    Route::middleware('role:super_admin,leads_admin,sales')->group(function () {
        Route::put('/leads/{lead}/sales', [LeadWorkflowController::class, 'salesUpdate'])
            ->name('leads.sales.update');
    });

    /*
    |----------------------------------------------------------------------
    | Phase 5.1 demo lifecycle (Live/Offline: Super Admin, Leads Admin,
    | assigned Developer — enforced in the form request. Force delete:
    | Super Admin / Leads Admin only — enforced in the controller).
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin,leads_admin,developer')->group(function () {
        Route::put('/leads/{lead}/demo-status', [LeadWorkflowController::class, 'demoStatusUpdate'])
            ->name('leads.demo-status.update');
        Route::delete('/leads/{lead}/demo', [LeadWorkflowController::class, 'forceDeleteDemo'])
            ->name('leads.demo.force-delete');
    });

    /*
    |----------------------------------------------------------------------
    | User management (Super Admin only)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->group(function () {
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::resource('users', UserController::class)->except(['show']);
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
