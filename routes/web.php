<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\ItemBankSearchController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\FundClusterController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormContentController;
use App\Http\Controllers\Admin\RfqDocumentController;
use App\Http\Controllers\Admin\ProcurementModeController;
use App\Http\Controllers\Admin\ProcurementModePhaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProcurementSigningController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ItemBankController;
use App\Http\Controllers\NotificationController;


Route::get('/', function() {
    return redirect()->route((auth()->check() ? 'dashboard' : 'login'));
});

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index')->middleware('permission:suppliers.view');
        Route::get('/archive', [SupplierController::class, 'archivedIndex'])->name('archive')->middleware('permission:suppliers.view');
        Route::get('/create', [SupplierController::class, 'create'])->name('create')->middleware('permission:suppliers.add');
        Route::post('/', [SupplierController::class, 'store'])->name('store')->middleware('permission:suppliers.add');
        Route::patch('/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('toggle-active')->middleware('permission:suppliers.edit');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit')->middleware('permission:suppliers.edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update')->middleware('permission:suppliers.edit');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy')->middleware('permission:suppliers.delete');
        Route::post('/{supplier}/documents', [SupplierController::class, 'uploadDocument'])->name('documents.store')->middleware('permission:suppliers.edit');
        Route::get('/{supplier}/documents/{document}', [SupplierController::class, 'downloadDocument'])->name('documents.download')->middleware('permission:suppliers.view');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show')->middleware('permission:suppliers.view');
    });

    Route::prefix('item-bank')->name('item-bank.')->group(function () {
        Route::get('/', [ItemBankController::class, 'index'])->name('index')->middleware('permission:items.view');
        Route::get('/archive', [ItemBankController::class, 'archivedIndex'])->name('archive')->middleware('permission:items.view');
        Route::post('/', [ItemBankController::class, 'store'])->name('store')->middleware('permission:items.add');
        Route::patch('/{item}/toggle-active', [ItemBankController::class, 'toggleActive'])->name('toggle-active')->middleware('permission:items.edit');
        Route::get('/{item}/edit', [ItemBankController::class, 'edit'])->name('edit')->middleware('permission:items.edit');
        Route::put('/{item}', [ItemBankController::class, 'update'])->name('update')->middleware('permission:items.edit');
        Route::delete('/{item}', [ItemBankController::class, 'destroy'])->name('destroy')->middleware('permission:items.delete');
    });

    Route::prefix('procurements')->name('procurements.')->group(function () {
        Route::get('/', [ProcurementController::class, 'index'])->name('index')->middleware('permission:procurement.view');
        Route::get('/archive', [ProcurementController::class, 'archivedIndex'])->name('archive');
        Route::get('/create/{mode_id?}', [ProcurementController::class, 'create'])->name('create')->middleware('permission:procurement.add');
        Route::post('/', [ProcurementController::class, 'store'])->name('store')->middleware('permission:procurement.add');

        Route::prefix('{procurement}')->group(function () {
            // CRUD
            Route::get('/', [ProcurementController::class, 'show'])->name('show')->middleware(['permission:procurement.view', 'office.scope']);
            Route::get('/attachments/{attachmentId}/file', [ProcurementController::class, 'attachmentFile'])->where('attachmentId', '[A-Za-z0-9_-]+')->name('attachments.file');
            Route::put('/', [ProcurementController::class, 'update'])->name('update')->middleware('office.scope');
            Route::delete('/', [ProcurementController::class, 'destroy'])->name('destroy')->middleware('permission:procurement.delete');

            // Attachment management
            Route::post('/attachments/{attachmentId}/save', [ProcurementController::class, 'saveAttachment'])->where('attachmentId', '[A-Za-z0-9_-]+')->name('attachments.save');
            Route::post('/attachments/{attachmentId}/rfq-documents/{rfqDocumentId}', [ProcurementController::class, 'toggleRfqDocument'])->whereNumber('attachmentId')->whereNumber('rfqDocumentId')->name('attachments.rfq-documents.toggle');
            Route::get('/attachments/{attachmentId}/signing-status', [ProcurementSigningController::class, 'status'])->where('attachmentId', '[A-Za-z0-9_-]+')->name('attachments.signing-status')->middleware(['permission:procurement.view', 'office.scope']);
            Route::post('/attachments/{attachmentId}/sign', [ProcurementSigningController::class, 'sign'])->where('attachmentId', '[A-Za-z0-9_-]+')->name('attachments.sign');
            Route::post('/attachments/{attachmentId}/route', [ProcurementController::class, 'routeAttachment'])->where('attachmentId', '[A-Za-z0-9_-]+')->name('attachments.route')->middleware('privilege:can_route');
            Route::post('/route-phase-attachments', [ProcurementController::class, 'routePhaseAttachments'])->name('route-phase-attachments')->middleware('privilege:can_route');
            // Phase workflow actions
            Route::post('/proceed-next-phase', [ProcurementController::class, 'proceedNextPhase'])->name('proceed-next-phase')->middleware('privilege:can_route');
            Route::post('/cancel', [ProcurementController::class, 'cancelRequest'])->name('cancel')->middleware(['privilege:can_cancel', 'office.scope']);
            Route::post('/archive', [ProcurementController::class, 'archiveRequest'])->name('archive-action')->middleware('office.scope');
            Route::post('/return-phase', [ProcurementController::class, 'returnToPhase'])->name('return-phase')->middleware('privilege:can_return');


        });
    });

    Route::get('/search/global', GlobalSearchController::class)->name('api.search.global');
    Route::get('/search/item-bank', ItemBankSearchController::class)->middleware('throttle:180,1')->name('api.search.item-bank');
    Route::get('/api/procurement-modes/{mode}/forms', [ProcurementModeController::class, 'getForms'])->name('api.procurement-modes.forms')->middleware('permission:modes.view');
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('/modes')->name('modes.')->middleware(['permission:modes.view'])->group(function () {
            Route::get('/', [ProcurementModeController::class, 'index'])->name('index');
            Route::get('/archive', [ProcurementModeController::class, 'archivedIndex'])->name('archive');
            Route::patch('/{mode}/archive-toggle', [ProcurementModeController::class, 'toggleArchive'])->name('archive-toggle')->middleware(['permission:modes.edit']);
            Route::get('/create', [ProcurementModeController::class, 'create'])->name('create')->middleware(['permission:modes.add']);
            Route::post('/', [ProcurementModeController::class, 'store'])->name('store')->middleware(['permission:modes.add']);
            Route::get('/{mode}/edit', [ProcurementModeController::class, 'edit'])->name('edit')->middleware(['permission:modes.edit']);
            Route::put('/{mode}', [ProcurementModeController::class, 'update'])->name('update')->middleware(['permission:modes.edit']);
            Route::get('/{mode}/builder', [ProcurementModeController::class, 'builder'])->name('builder')->middleware(['permission:modes.edit']);
            Route::prefix('/{mode}/phases')->name('phases.')->group(function () {
                Route::post('/', [ProcurementModePhaseController::class, 'store'])->name('store')->middleware('permission:modes.edit');
                Route::post('/reorder', [ProcurementModePhaseController::class, 'reorder'])->name('reorder')->middleware('permission:modes.edit');
                Route::put('/{phase}', [ProcurementModePhaseController::class, 'update'])->name('update')->middleware('permission:modes.edit');
                Route::delete('/{phase}', [ProcurementModePhaseController::class, 'destroy'])->name('destroy')->middleware('permission:modes.edit');
                Route::post('/{phase}/roles', [ProcurementModePhaseController::class, 'syncRoles'])->name('sync-roles')->middleware('permission:modes.edit');
                Route::post('/{phase}/documents', [ProcurementModePhaseController::class, 'storeDocumentRequirement'])->name('documents.store')->middleware('permission:modes.edit');
                Route::delete('/{phase}/documents/{document}', [ProcurementModePhaseController::class, 'destroyDocumentRequirement'])->name('documents.destroy')->middleware('permission:modes.edit');
            });
        });

        Route::prefix('/fundclusters')->name('fundclusters.')->group(function () {
            Route::get('/', [FundClusterController::class, 'index'])->name('index')->middleware('permission:fundclusters.view');
            Route::get('/archive', [FundClusterController::class, 'archivedIndex'])->name('archive')->middleware('permission:fundclusters.view');
            Route::post('/', [FundClusterController::class, 'store'])->name('store')->middleware('permission:fundclusters.add');
            Route::put('/{fundCluster}', [FundClusterController::class, 'update'])->name('update')->middleware('permission:fundclusters.edit');
            Route::patch('/{fundCluster}/toggle', [FundClusterController::class, 'toggle'])->name('toggle')->middleware('permission:fundclusters.edit');
        });


        Route::prefix('/forms')->name('forms.')->middleware('permission:forms.view')->group(function () {
            Route::get('/', [FormController::class, 'index'])->name('index');
            Route::get('/archive', [FormController::class, 'archivedIndex'])->name('archive');
            Route::patch('/{form}/archive', [FormController::class, 'archive'])->name('archive-action')->middleware('permission:forms.delete');
            Route::patch('/{form}/restore', [FormController::class, 'restore'])->name('restore')->middleware('permission:forms.edit');
            Route::get('/create', [FormController::class, 'create'])->name('create')->middleware('permission:forms.add');
            Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit')->middleware('permission:forms.edit');
            Route::post('/', [FormController::class, 'store'])->name('store')->middleware('permission:forms.add');
            Route::get('/{form}/designer', [FormController::class, 'designer'])->name('designer')->middleware('permission:forms.edit');
            Route::put('/{form}/designer', [FormController::class, 'saveDesign'])->name('designer.save')->middleware('permission:forms.edit');
            Route::put('/{form}', [FormController::class, 'update'])->name('update')->middleware('permission:forms.edit');
            Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy')->middleware('permission:forms.delete');
            Route::patch('/{form}/publish', [FormController::class, 'publish'])->name('publish')->middleware('permission:forms.edit');
            Route::patch('/{form}/unpublish', [FormController::class, 'unpublish'])->name('unpublish')->middleware('permission:forms.edit');
            Route::get('/{form}/file', [FormController::class, 'serveFile'])->name('file');
        });

        Route::get('/form-cms', [FormContentController::class, 'index'])->name('form-cms.index')->middleware('permission:forms.view');
        Route::put('/form-cms', [FormContentController::class, 'update'])->name('form-cms.update')->middleware('permission:forms.edit');

        Route::prefix('/rfq-documents')->name('rfq-documents.')->group(function () {
            Route::get('/', [RfqDocumentController::class, 'index'])->name('index')->middleware('permission:rfq_documents.view');
            Route::get('/archive', [RfqDocumentController::class, 'archive'])->name('archive')->middleware('permission:rfq_documents.view');
            Route::post('/', [RfqDocumentController::class, 'store'])->name('store')->middleware('permission:rfq_documents.add');
            Route::put('/{rfqDocument}', [RfqDocumentController::class, 'update'])->name('update')->middleware('permission:rfq_documents.edit');
            Route::patch('/{rfqDocument}/toggle', [RfqDocumentController::class, 'toggle'])->name('toggle')->middleware('permission:rfq_documents.edit');
            Route::delete('/{rfqDocument}', [RfqDocumentController::class, 'destroy'])->name('destroy')->middleware('permission:rfq_documents.delete');
            Route::patch('/reorder', [RfqDocumentController::class, 'reorder'])->name('reorder')->middleware('permission:rfq_documents.edit');
        });

        Route::prefix('/offices')->name('offices.')->group(function () {
            Route::get('/', [OfficeController::class, 'index'])->name('index')->middleware(['permission:offices.view']);
            Route::get('/archive', [OfficeController::class, 'archivedIndex'])->name('archive')->middleware(['permission:offices.view']);
            Route::post('/', [OfficeController::class, 'store'])->name('store')->middleware(['permission:offices.add']);
            Route::put('/{office}', [OfficeController::class, 'update'])->name('update')->middleware(['permission:offices.edit']);
            Route::patch('/{office}/toggle', [OfficeController::class, 'toggleStatus'])->name('toggle')->middleware(['permission:offices.toggle_active']);
        });

        Route::prefix('/roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index')->middleware(['permission:roles.view']);
            Route::post('/', [RoleController::class, 'store'])->name('store')->middleware(['permission:roles.add']);
            Route::post('/{role}/matrix', [RoleController::class, 'updateMatrix'])->name('update-matrix')->middleware(['permission:roles.edit']);
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware(['permission:roles.delete']);
        });


        Route::prefix('/users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware(['permission:users.view']);
            Route::post('/', [UserController::class, 'store'])->name('store')->middleware(['permission:users.add']);
            Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware(['permission:users.edit']);
        });

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')->middleware(['permission:settings.view']);
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware(['permission:settings.update']);
    });

    Route::get('/reports/gppb-compliance', [ReportController::class, 'gppbCompliance'])->name('reports.gppb-compliance')->middleware(['permission:reports.view']);
});
