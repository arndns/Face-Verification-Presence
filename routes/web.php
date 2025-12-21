<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FaceApiController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Send authenticated users straight to their dashboard to avoid redirect loops
    if (auth()->check()) {
        return auth()->user()->role === 'admin'
            ? redirect()->route('admin.index')
            : redirect()->route('employee.index');
    }

    return redirect()->route('login');
});

// Fallback dashboard route used by guest middleware when the user is already authenticated
Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->role === 'admin'
        ? redirect()->route('admin.index')
        : redirect()->route('employee.index');
})->name('dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/admin/data', [AdminController::class, 'viewdata'])->name('admin.data');
        Route::get('/admin/create', [AdminController::class, 'create'])->name('admin.create');
        Route::post('/store', [AdminController::class, 'store'])->name('admin.store');
        Route::get('/admin/{employee}/edit', [AdminController::class, 'editdata'])->name('admin.edit');
        Route::put('/admin/{employee}/update', [AdminController::class, 'update'])->name('admin.update');
        Route::delete('/users/{employee}/delete', [AdminController::class, 'destroy'])->name('admin.delete');
        Route::get('/admin/{employee}/addface', [FaceApiController::class, 'addfaceid'])->name('admin.faceid');
        Route::get('/admin/location', [LocationController::class, 'locindex'])->name('location.index');
        Route::get('/admin/location/create', [LocationController::class, 'create'])->name('location.create');
        Route::post('/admin/location/store', [LocationController::class, 'store'])->name('location.store');
        Route::get('/admin/location/{location}/edit', [LocationController::class, 'edit'])->name('location.edit');
        Route::patch('/admin/location/{location}/update', [LocationController::class, 'update'])->name('location.update');
        Route::delete('/admin/location/{location}/delete', [LocationController::class, 'destroy'])->name('location.delete');

        Route::resource('/admin/shifts', ShiftController::class)->except(['show']);
        Route::get('/admin/presence/history', [AdminController::class, 'presenceHistory'])->name('admin.presence.history');
        Route::get('/admin/permit', [AdminController::class, 'permitIndex'])->name('admin.permit.index');
        Route::post('/admin/permit/{permit}/approve', [AdminController::class, 'approvePermit'])->name('admin.permit.approve');
        Route::post('/admin/permit/{permit}/reject', [AdminController::class, 'rejectPermit'])->name('admin.permit.reject');
        Route::put('/admin/permit/{permit}/update', [AdminController::class, 'updatePermit'])->name('admin.permit.update');
        Route::delete('/admin/permit/{permit}', [AdminController::class, 'destroyPermit'])->name('admin.permit.destroy');
    });

    Route::middleware('role:employee')->group(function () {
        Route::get('/employee/dashboard', [EmployeeController::class, 'index'])->name('employee.index');
        Route::get('/employee/camera', [EmployeeController::class, 'camera'])->name('employee.camera');
        Route::post('/employee/presence/store', [EmployeeController::class, 'presence'])->name('employee.store');
        Route::get('/employee/presence/status', [EmployeeController::class, 'presenceStatus'])->name('employee.presence.status');
        Route::get('employee/history/presence', [EmployeeController::class, 'history_presence'])->name('employee.presence.history');
        Route::get('/employee/permit/create', [EmployeeController::class, 'createPermit'])->name('employee.permit.create');
        Route::post('/employee/leave/store', [EmployeeController::class, 'storePermit'])->name('employee.permit.store');
        Route::get('/employee/leave/history', [EmployeeController::class, 'permitHistory'])->name('employee.permit.history');
        Route::delete('/employee/leave/{permit}', [EmployeeController::class, 'destroyPermit'])->name('employee.permit.destroy');
        Route::get('/employee/profile', [EmployeeController::class, 'profile'])->name('employee.profile');
        Route::post('/employee/profile/password', [EmployeeController::class, 'updatePassword'])->name('employee.update.password');
    });

    // Fallback for storage files if symlink is missing
    Route::get('/storage-file/{path}', function ($path) {
        $path = storage_path('app/public/' . $path);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path);
    })->where('path', '.*')->name('storage.file');
    
    // API-like route for getting employee face embedding (used by camera page)
    Route::get('/api/employee/embedding', [EmployeeController::class, 'getEmbedding'])->name('api.employee.embedding');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
