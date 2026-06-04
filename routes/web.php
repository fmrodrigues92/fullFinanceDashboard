<?php

use App\Http\Controllers\Companies\CompanyController;
use App\Http\Controllers\Companies\CompanyPartnerController;
use App\Http\Controllers\Companies\ProlaboreConfigController;
use App\Http\Controllers\Companies\ProlaboreRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Invoicing\ClientController;
use App\Http\Controllers\Invoicing\InvoiceController;
use App\Http\Controllers\Invoicing\SimulationBatchController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::group(['prefix' => 'companies', 'as' => 'companies.'], function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::post('/', [CompanyController::class, 'store'])->name('store');
        Route::get('/{company}', [CompanyController::class, 'show'])->name('show');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy');

        Route::put('/{company}/partners', [CompanyPartnerController::class, 'update'])->name('partners.sync');

        Route::group(['prefix' => '{company}/prolabore-configs', 'as' => 'prolabore-configs.'], function () {
            Route::get('/', [ProlaboreConfigController::class, 'index'])->name('index');
            Route::post('/', [ProlaboreConfigController::class, 'store'])->name('store');
            Route::put('/{config}', [ProlaboreConfigController::class, 'update'])->name('update');
            Route::delete('/{config}', [ProlaboreConfigController::class, 'destroy'])->name('destroy');
        });

        Route::group(['prefix' => '{company}/prolabore-records', 'as' => 'prolabore-records.'], function () {
            Route::get('/', [ProlaboreRecordController::class, 'index'])->name('index');
            Route::post('/', [ProlaboreRecordController::class, 'store'])->name('store');
            Route::put('/{record}', [ProlaboreRecordController::class, 'update'])->name('update');
            Route::delete('/{record}', [ProlaboreRecordController::class, 'destroy'])->name('destroy');
        });

        Route::group(['prefix' => '{company}/clients', 'as' => 'clients.'], function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::post('/', [ClientController::class, 'store'])->name('store');
            Route::put('/{client}', [ClientController::class, 'update'])->name('update');
            Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
        });

        Route::group(['prefix' => '{company}/invoices', 'as' => 'invoices.'], function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
            Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
            Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
        });

        Route::group(['prefix' => '{company}/simulation-batches', 'as' => 'simulation-batches.'], function () {
            Route::get('/', [SimulationBatchController::class, 'index'])->name('index');
            Route::post('/', [SimulationBatchController::class, 'store'])->name('store');
            Route::delete('/{simulationBatch}', [SimulationBatchController::class, 'destroy'])->name('destroy');
        });
    });
});

require __DIR__.'/settings.php';
