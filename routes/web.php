<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\FileController;
use App\Http\Controllers\GlobalSearchController;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ApiClientController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\ContactGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PhonebookController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SmsController;

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
Route::get('/image/{table}/{name}', [FileController::class, 'viewImage']);

Route::middleware(['auth', 'no.cache', 'password.change.required', 'route.access'])->group(function () 
{
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search/autocomplete', [GlobalSearchController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    Route::group(array('prefix' => 'account', 'middleware' => []), function() {
        Route::get('/', [AccountController::class,'index'])->name('account.index');
        Route::get('all', [AccountController::class, 'list'])->name('account.list');
        Route::post('create', [AccountController::class, 'create'])->name('account.create');
        Route::post('validate', [AccountController::class, 'validateRequest'])->name('account.validate');
        Route::get('data/{id}', [AccountController::class, 'get'])->name('account.get');
        Route::post('update/{id}', [AccountController::class, 'update'])->name('account.update');
        Route::post('regenerate-password/{id}', [AccountController::class, 'regeneratePassword'])->name('account.regenerate_password');
        Route::post('delete/{account_number}', [AccountController::class, 'destroy'])->name('account.destroy');
        Route::post('mass-remove', [AccountController::class, 'massRemove'])->name('account.mass_remove');
    });

    Route::group(array('prefix' => 'audit-trail', 'middleware' => []), function() {
        Route::get('/', [AuditTrailController::class,'index'])->name('audit-trail.index');
        Route::get('all', [AuditTrailController::class, 'list'])->name('audit-trail.list');
        Route::get('data/{id}', [AuditTrailController::class, 'get'])->name('audit-trail.get');
    });

    Route::group(array('prefix' => 'api-client', 'middleware' => []), function() {
        Route::get('/', [ApiClientController::class,'index'])->name('api-client.index');
        Route::get('all', [ApiClientController::class, 'list'])->name('api-client.list');
        Route::post('create', [ApiClientController::class, 'create'])->name('api-client.create');
        Route::post('validate', [ApiClientController::class, 'validateRequest'])->name('api-client.validate');
        Route::get('data/{id}', [ApiClientController::class, 'get'])->name('api-client.get');
        Route::post('update/{id}', [ApiClientController::class, 'update'])->name('api-client.update');
        Route::post('regenerate-secret/{id}', [ApiClientController::class, 'regenerateSecret'])->name('api-client.regenerate_secret');
        Route::post('delete/{id}', [ApiClientController::class, 'destroy'])->name('api-client.destroy');
    });

    Route::group(array('prefix' => 'role', 'middleware' => []), function() {
        Route::get('/', [RoleController::class,'index'])->name('role.index');
        Route::get('all', [RoleController::class, 'list'])->name('role.list');
        Route::post('create', [RoleController::class, 'create'])->name('role.create');
        Route::post('validate', [RoleController::class, 'validateRequest'])->name('role.validate');
        Route::get('data/{id}', [RoleController::class, 'get'])->name('role.get');
        Route::post('update/{id}', [RoleController::class, 'update'])->name('role.update');
        Route::post('delete/{id}', [RoleController::class, 'destroy'])->name('role.destroy');
        Route::post('mass-remove', [RoleController::class, 'massRemove'])->name('role.mass_remove');
    });

    Route::group(array('prefix' => 'department', 'middleware' => []), function() {
        Route::get('/', [DepartmentController::class,'index'])->name('department.index');
        Route::get('all', [DepartmentController::class, 'list'])->name('department.list');
        Route::post('create', [DepartmentController::class, 'create'])->name('department.create');
        Route::post('validate', [DepartmentController::class, 'validateRequest'])->name('department.validate');
        Route::get('data/{id}', [DepartmentController::class, 'get'])->name('department.get');
        Route::post('update/{id}', [DepartmentController::class, 'update'])->name('department.update');
        Route::post('delete/{id}', [DepartmentController::class, 'destroy'])->name('department.destroy');
        Route::post('mass-remove', [DepartmentController::class, 'massRemove'])->name('department.mass_remove');
    });

    Route::group(array('prefix' => 'position', 'middleware' => []), function() {
        Route::get('/', [PositionController::class,'index'])->name('position.index');
        Route::get('all', [PositionController::class, 'list'])->name('position.list');
        Route::post('create', [PositionController::class, 'create'])->name('position.create');
        Route::post('validate', [PositionController::class, 'validateRequest'])->name('position.validate');
        Route::get('data/{id}', [PositionController::class, 'get'])->name('position.get');
        Route::post('update/{id}', [PositionController::class, 'update'])->name('position.update');
        Route::post('delete/{id}', [PositionController::class, 'destroy'])->name('position.destroy');
        Route::post('mass-remove', [PositionController::class, 'massRemove'])->name('position.mass_remove');
    });

    Route::group(array('prefix' => 'phonebook', 'middleware' => []), function() 
    {
        Route::get('/', [PhonebookController::class, 'index'])->name('phonebook.index');
        Route::get('all', [PhonebookController::class, 'list'])->name('phonebook.list');
        Route::get('template', [PhonebookController::class, 'template'])->name('phonebook.template');
        Route::post('create', [PhonebookController::class, 'create'])->name('phonebook.create');
        Route::post('import', [PhonebookController::class, 'import'])->name('phonebook.import');
        Route::post('validate', [PhonebookController::class, 'validateRequest'])->name('phonebook.validate');
        Route::get('data/{id}', [PhonebookController::class, 'get'])->name('phonebook.get');
        Route::post('update/{id}', [PhonebookController::class, 'update'])->name('phonebook.update');
        Route::post('delete/{id}', [PhonebookController::class, 'destroy'])->name('phonebook.destroy');
        Route::post('mass-remove', [PhonebookController::class, 'massRemove'])->name('phonebook.mass_remove');
    });

    Route::group(array('prefix' => 'group', 'middleware' => []), function() 
    {
        Route::get('/', [ContactGroupController::class, 'index'])->name('contact_group.index');
        Route::get('all', [ContactGroupController::class, 'list'])->name('contact_group.list');
        Route::post('create', [ContactGroupController::class, 'create'])->name('contact_group.create');
        Route::post('validate', [ContactGroupController::class, 'validateRequest'])->name('contact_group.validate');
        Route::get('data/{id}', [ContactGroupController::class, 'get'])->name('contact_group.get');
        Route::post('{id}/contacts/add', [ContactGroupController::class, 'attachContacts'])->name('contact_group.attach_contacts');
        Route::post('{id}/contacts/{contactId}/remove', [ContactGroupController::class, 'detachContact'])->name('contact_group.detach_contact');
        Route::post('update/{id}', [ContactGroupController::class, 'update'])->name('contact_group.update');
        Route::post('delete/{id}', [ContactGroupController::class, 'destroy'])->name('contact_group.destroy');
        Route::post('mass-remove', [ContactGroupController::class, 'massRemove'])->name('contact_group.mass_remove');
    });

    Route::group(array('prefix' => 'sms', 'middleware' => []), function() 
    {
        Route::get('/', [SmsController::class, 'index'])->name('sms.index');
        Route::get('all', [SmsController::class, 'list'])->name('sms.list');
        Route::post('create', [SmsController::class, 'create'])->name('sms.create');
        Route::post('validate', [SmsController::class, 'validateRequest'])->name('sms.validate');
        Route::get('data/{id}', [SmsController::class, 'get'])->name('sms.get');
        Route::post('update/{id}', [SmsController::class, 'update'])->name('sms.update');
        Route::post('delete/{id}', [SmsController::class, 'destroy'])->name('sms.destroy');
        Route::post('mass-remove', [SmsController::class, 'massRemove'])->name('sms.mass_remove');
        
        Route::get('compose',          [SmsController::class, 'compose'])->name('sms.compose');
        Route::post('send',            [SmsController::class, 'send'])->name('sms.send');
        Route::get('contacts/search',  [SmsController::class, 'searchContacts'])->name('sms.contacts.search');
    });
});

require __DIR__.'/auth.php';
