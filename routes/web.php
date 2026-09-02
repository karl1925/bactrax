<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProcurementRouteController;
use App\Http\Controllers\ProcurementDocumentController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\ProcurementDataTableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProcurementModeController;
use App\Http\Controllers\ProcurementPhaseController;
use App\Http\Controllers\PrintRoutingSlipController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\{ProfileController, ProfileSignatureController};
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Storage;


Route::get('/', [AuthenticatedSessionController::class, 'create']);


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Route::get('/profile/{tab}', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::put('/profile/{tab}', [ProfileController::class, 'update'])->name('profile.update');
    // Route::put('/profile/password/update/{tab}', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    // Route::post('/profile/signature/{tab}', [ProfileSignatureController::class, 'store',])->name('profile.signature.store');
    // Route::get('/profile/signature/download/{tab}', [ProfileSignatureController::class, 'download',])->name('profile.signature.download');
    // Route::put('/profile/signature/{tab}', [ProfileSignatureController::class, 'replace',])->name('profile.signature.replace');
    // Route::put('/profile/signature/password/{tab}', [ProfileSignatureController::class, 'updatePassword',])->name('profile.signature.password');
    // Route::delete('/profile/signature/delete-password/{tab}', [ProfileSignatureController::class, 'destroy',])->name('profile.signature.deletepassword');

    // Route::get(
    //     '/profile/signature/download/{tab}',
    //     [ProfileSignatureController::class, 'download']
    // )->name('profile.signature.download');

    // Route::get(
    //     '/profile/signature/image/{tab}',
    //     [ProfileSignatureController::class, 'image']
    // )->name('profile.signature.image');

    // Route::put(
    //     '/profile/signature/{tab}',
    //     [ProfileSignatureController::class, 'replace']
    // )->name('profile.signature.replace');

    // Route::put(
    //     '/profile/signature/password/{tab}',
    //     [ProfileSignatureController::class, 'updatePassword']
    // )->name('profile.signature.password');

    // Route::delete(
    //     '/profile/signature/password/{tab}',
    //     [ProfileSignatureController::class, 'deletePassword']
    // )->name('profile.signature.deletepassword');

    // Route::put(
    //     '/profile/signature/appearance/{tab}',
    //     [ProfileSignatureController::class, 'updateAppearance']
    // )->name('profile.signature.appearance');





    // Procurement Operations Routes
    Route::get('/procurements', [ProcurementController::class, 'index'])->name('procurements.index');
    Route::get('/procurements/create', [ProcurementController::class, 'create'])->name('procurements.create');
    Route::post('/procurements', [ProcurementController::class, 'store'])->name('procurements.store');
    Route::get('/procurements/{procurement}', [ProcurementController::class, 'show'])->name('procurements.show')->middleware('office.scope');
    Route::get('/procurements/{procurement}/edit', [ProcurementController::class, 'edit'])->name('procurements.edit');
    Route::put('/procurements/{procurement}/', [ProcurementController::class, 'update'])->name('procurements.update');
    Route::post('/procurements/{procurement}/upload', [ProcurementController::class, 'uploadDocument'])->name('procurements.documents.upload');

    // Procurement Workflow Actions
    Route::prefix('procurements/{procurement}')->name('procurements.')->group(function () {
        Route::post('/route', [ProcurementRouteController::class, 'route'])
            ->name('route')
            ->middleware('privilege:can_route');

        Route::post('/return', [ProcurementRouteController::class, 'return'])
            ->name('return')
            ->middleware('privilege:can_return');

        Route::post('/complete', [ProcurementRouteController::class, 'complete'])
            ->name('complete')
            ->middleware('privilege:can_complete');

        Route::post('/cancel', [ProcurementRouteController::class, 'cancel'])
            ->name('cancel')
            ->middleware('privilege:can_cancel');

        Route::post('/reject', [ProcurementRouteController::class, 'reject'])
            ->name('reject')
            ->middleware('privilege:can_reject');
    });

    //Procurement Docs
    Route::post('/procurements/{procurement}/documents', [ProcurementDocumentController::class, 'store'])
        ->name('procurements.documents.store')
        ->middleware('office.scope');

    Route::get('/procurements/{procurement}/documents/{document}/download', [ProcurementDocumentController::class, 'download'])
        ->name('procurements.documents.download')
        ->middleware('office.scope');

    Route::delete('/procurements/{procurement}/documents/{document}', [ProcurementDocumentController::class, 'destroy'])
        ->name('procurements.documents.destroy')
        ->middleware('office.scope');

    // AJAX Endpoint for DataTables Search and Filters
    Route::post('/procurements/datatable-data', ProcurementDataTableController::class)
        ->name('api.procurements.datatable');

    // AJAX Endpoint for Topbar/Modal Live Global Search
    Route::get('/search/global', GlobalSearchController::class)
        ->name('api.search.global');

    Route::get(
        '/return-targets/{phase}',
        [ProcurementController::class, 'returnTargetOffices']
    )->name('return-targets');

    Route::get(
        '/return-target-users/{phase}/{office}',
        [ProcurementController::class, 'returnTargetUsers']
    )->name('return-target-users');

    // Procurement Modes Directory & Workflow Builder (Protected by 'permission:modes.manage')
    Route::middleware(['permission:modes.view'])->group(function () {
        Route::get('/procurement-modes', [ProcurementModeController::class, 'index'])->name('procurement-modes.index');
        Route::get('/procurement-modes/create', [ProcurementModeController::class, 'create'])->name('procurement-modes.create')->middleware(['permission:modes.add']);
        Route::post('/procurement-modes', [ProcurementModeController::class, 'store'])->name('procurement-modes.store')->middleware(['permission:modes.add']);
        Route::get('/procurement-modes/{mode}/edit', [ProcurementModeController::class, 'edit'])->name('procurement-modes.edit')->middleware(['permission:modes.edit']);
        Route::put('/procurement-modes/{mode}', [ProcurementModeController::class, 'update'])->name('procurement-modes.update')->middleware(['permission:modes.edit']);
        Route::get('/procurement-modes/{mode}/builder', [ProcurementModeController::class, 'builder'])->name('procurement-modes.builder')->middleware(['permission:modes.edit']);

        // Dynamic Workflow Phase Management
        Route::post('/procurement-modes/{mode}/phases', [ProcurementPhaseController::class, 'store'])->name('procurement-phases.store');
        Route::put('/procurement-modes/{mode}/phases/{phase}', [ProcurementPhaseController::class, 'update'])->name('procurement-phases.update');
        Route::delete('/procurement-modes/{mode}/phases/{phase}', [ProcurementPhaseController::class, 'destroy'])->name('procurement-phases.destroy');
        Route::post('/procurement-modes/{mode}/phases/{phase}/roles', [ProcurementPhaseController::class, 'syncRoles'])->name('procurement-phases.sync-roles');
        Route::post('/procurement-modes/{mode}/phases/{phase}/documents', [ProcurementPhaseController::class, 'storeDocumentRequirement'])->name('procurement-phases.documents.store');
        Route::delete('/procurement-modes/{mode}/phases/{phase}/documents/{document}', [ProcurementPhaseController::class, 'destroyDocumentRequirement'])->name('procurement-phases.documents.destroy');
    });

    // print routing
    Route::get('/procurements/{procurement}/print-routing-slip', PrintRoutingSlipController::class)
        ->name('procurements.print-routing-slip');

    // System Administration Routes (Protected by permission checks)
    Route::prefix('admin')->name('admin.')->group(function () {
        // System Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')->middleware(['permission:settings.view']);
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware(['permission:settings.update']);

        Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware(['permission:users.view']);
        Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware(['permission:users.add']);
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware(['permission:users.edit']);

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index')->middleware(['permission:roles.view']);
        Route::post('/roles/{role}/matrix', [RoleController::class, 'updateMatrix'])->name('roles.update-matrix')->middleware(['permission:roles.edit']);

        // Office Management Routes
        Route::get('/offices', [OfficeController::class, 'index'])->name('offices.index')->middleware(['permission:offices.view']);
        Route::post('/offices', [OfficeController::class, 'store'])->name('offices.store')->middleware(['permission:offices.add']);
        Route::put('/offices/{office}', [OfficeController::class, 'update'])->name('offices.update')->middleware(['permission:offices.edit']);
        Route::patch('/offices/{office}/toggle', [OfficeController::class, 'toggleStatus'])->name('offices.toggle');
    });

    // Statutory Reports
    Route::get('/reports/gppb-compliance', [ReportController::class, 'gppbCompliance'])->name('reports.gppb-compliance')->middleware(['permission:reports.view']);
});

Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');


    /*
    |--------------------------------------------------------------------------
    | Signature
    |--------------------------------------------------------------------------
    */

    Route::prefix('/signature')->name('signature.')->group(function () {
        // Management UI & Certificate Routes
        Route::get('/', [ProfileSignatureController::class, 'edit'])->name('edit');
        Route::post('/', [ProfileSignatureController::class, 'store'])->name('store');
        Route::put('/replace', [ProfileSignatureController::class, 'replace'])->name('replace');
        Route::put('/password/update', [ProfileSignatureController::class, 'updatePassword'])->name('password.update');
        Route::delete('/password/delete', [ProfileSignatureController::class, 'deletePassword'])->name('password.delete');
        Route::put('/appearance', [ProfileSignatureController::class, 'updateAppearance'])->name('appearance.update');
        Route::get('/image', [ProfileSignatureController::class, 'image'])->name('image');
        Route::get('/download', [ProfileSignatureController::class, 'downloadCertificate'])->name('download');

        // Document Preparation & Signing Actions
        Route::post('/set-fields', [ProfileSignatureController::class, 'setFields'])->name('set-fields');
        Route::get('/prepared', [ProfileSignatureController::class, 'getPreparedPdf'])->name('prepared');
        Route::delete('/prepared', [ProfileSignatureController::class, 'deletePreparedFile'])->name('prepared.delete');
        Route::post('/sign', [ProfileSignatureController::class, 'signDocument'])->name('sign');

        // Signed Document Download
        Route::get('/signed/download', [ProfileSignatureController::class, 'downloadSigned'])->name('signed.download');
    });
});
