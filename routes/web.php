<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\LicenseeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ProfileUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LicenseeTemplateController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\SiteConfigurationController;
use App\Http\Controllers\Auth\PasswordChangeController;
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

Auth::routes();
//Language Translation
Route::get('/check-lang', function () {
    return 'Current locale: ' . app()->getLocale();
});

Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);
Route::get('index', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
Route::get('apps-tasks-kanban', [App\Http\Controllers\HomeController::class, 'index'])->name('apps-tasks-kanban');
Route::get('pages-faqs', [App\Http\Controllers\HomeController::class, 'index'])->name('pages-faqs');



Route::middleware(['auth'])->group(function () {
    Route::controller(LicenseeController::class)->prefix('licensees')->name('licensees.')->group(function () {

        Route::get('/', 'index')->name('index');                  
        Route::get('/create', 'create')->name('create');          
        Route::post('/', 'store')->name('store');                 
        Route::get('/{licenseeId}/edit', 'edit')->name('edit');     
        Route::put('/{licenseeId}', 'update')->name('update');      
        Route::patch('/{licenseeId}/archive', 'archive')->name('archive'); 

        Route::get('subfolders/', 'subfolders')->name('subfolders');
    });

    Route::controller(DepartmentController::class)->prefix('departments')->name('departments.')->group(function () {

        Route::get('/', 'index')->name('index');                  
        Route::get('/create', 'create')->name('create');          
        Route::post('/', 'store')->name('store');                 
        Route::get('/{departmentId}/edit', 'edit')->name('edit');     
        Route::put('/{departmentId}', 'update')->name('update');      
        Route::patch('/{departmentId}/archive', 'archive')->name('archive'); 
    });

    Route::controller(ProfileUserController::class)->prefix('security')->name('security.')->group(function () {

        Route::get('users/', 'index')->name('profile_users.index');                  
        Route::get('users/create', 'create')->name('profile_users.create');          
        Route::post('users/', 'store')->name('profile_users.store');                 
        Route::get('users/{id}/edit', 'edit')->name('profile_users.edit');     
        Route::put('users/{id}', 'update')->name('profile_users.update');      
        Route::patch('users/{id}/archive', 'archive')->name('profile_users.archive'); 

    });

    Route::controller(RoleController::class)->prefix('security')->name('security.')->group(function () {
        Route::get('roles/', 'index')->name('roles.index');                  
        Route::get('/roles/{role}/permissions', 'editPermissions')->name('roles.permissions.edit');
        Route::post('/roles/{role}/permissions', 'updatePermissions')->name('roles.permissions.update');  
        Route::put('/roles/{id}',  'update')->name('roles.update');       
    });

    Route::controller(LicenseeTemplateController::class)->prefix('forms')->name('forms.')->group(function () {
        Route::get('licensee_templates', 'index')->name('licensee_templates');
        Route::get('licensee_templates/create', 'create')->name('licensee_templates.create'); 
        Route::post('licensee_templates', 'store')->name('licensee_templates.store'); 
        Route::get('licensee_templates/{licenseeTemplate}/edit', 'edit')->name('licensee_templates.edit'); 
        Route::put('licensee_templates/update', 'update')->name('licensee_templates.update'); 
        Route::get('licensee_templates/destroy', 'destroy')->name('licensee_templates.destroy'); 
        Route::post('licensee_templates/{template}/keys',  'storeKey')->name('licensee_templates.keys.store');
        Route::put('licensee_templates/keys/{key}',  'updateKey')->name('licensee_templates.keys.update');
        Route::delete('licensee_templates/keys/{key}', [LicenseeTemplateController::class, 'deleteKey'])->name('licensee_templates.keys.delete');  
          
    });

    Route::controller(AssessmentController::class)->prefix('assessments')->name('assessments.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/show/{assessment}', 'show')->name('show');
        Route::get('/create', 'create')->name('create');
        Route::post('/upload', 'upload')->name('upload');
        Route::post('/commit', 'commitMasterData')->name('commit');

        Route::post('/store', 'store')->name('store'); // for manual create (no file)
        Route::get('/{assessment}/upload', 'showUploadForm')->name('upload.form');
        Route::get('/{assessment}/form', 'showManualForm')->name('form');
        Route::post('/{assessment}/form/submit', 'submitManualForm')->name('form.submit');
        Route::post('/{assessment}/manual-store','storeManualSheet')->name('manual.store');
        Route::get('/{assessment}/clear-data', 'clearData')->name('clearData');
        Route::get('/{assessment}/sheet/{sheetId}/archive/{entryId}', 'archiveSheetEntry')->name('sheet.archiveSheetEntry');

        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::put('/{assessment}', 'update')->name('update');


        Route::post('/{assessment}/import-data', 'importData')->name('importData');

    });

    Route::controller(SiteConfigurationController::class)->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/update', 'update')->name('update');
    });

    Route::get('/my-profile', [ProfileUserController::class, 'profile'])->name('profileuser.show');
    Route::get('/my-profile/edit', [ProfileUserController::class, 'profileEdit'])->name('profileuser.edit');
    Route::post('/my-profile/update', [ProfileUserController::class, 'profileUpdate'])->name('profileuser.update');
    Route::post('/my-profile/change-password', [ProfileUserController::class, 'changePassword'])->name('profileuser.changePassword');

    

});

Route::get('/password/change', [PasswordChangeController::class, 'showForm'])->name('password.change');
Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');

Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root');

//Update User Details
Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

//Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
