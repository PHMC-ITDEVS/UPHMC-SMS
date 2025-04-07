<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\FileController;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function () 
{
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::group(array('prefix' => 'account', 'middleware' => []), function() {
        Route::get('/', [AccountController::class,'index'])->name('account.index');
        Route::get('all', [AccountController::class, 'list'])->name('account.list');
        Route::post('create', [AccountController::class, 'create'])->name('account.create');
        Route::post('validate', [AccountController::class, 'validateRequest'])->name('account.validate');
        Route::post('data/{id}', [AccountController::class, 'get'])->name('account.get');
        Route::post('update/{id}', [AccountController::class, 'update'])->name('account.update');
        Route::post('delete', [AccountController::class, 'destroy'])->name('account.destroy');
        Route::post('mass-remove', [AccountController::class, 'massRemove'])->name('account.mass_remove');
    });
});

require __DIR__.'/auth.php';
