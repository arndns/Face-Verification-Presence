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
    return redirect('/login');
});

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
        Route::get('/admin/leave', [AdminController::class, 'leaveIndex'])->name('admin.leave.index');
        Route::post('/admin/leave/{leave}/approve', [AdminController::class, 'approveLeave'])->name('admin.leave.approve');
        Route::post('/admin/leave/{leave}/reject', [AdminController::class, 'rejectLeave'])->name('admin.leave.reject');
    });

    Route::middleware('role:employee')->group(function () {
        Route::get('/employee/dashboard', [EmployeeController::class, 'index'])->name('employee.index');
        Route::get('/employee/camera', [EmployeeController::class, 'webcam'])->name('employee.camera');
        Route::get('/api/employee/embedding', [EmployeeController::class, 'faceMatcher']);
        Route::get('/employee/presence/status', [EmployeeController::class, 'presenceStatus'])->name('employee.presence.status');
        Route::post('/presence/store', [EmployeeController::class, 'presence']);
        Route::get('employee/history/presence', [EmployeeController::class, 'history_presence'])->name('employee.presence.history');
        Route::get('/employee/leave/create', [EmployeeController::class, 'createLeave'])->name('employee.leave.create');
        Route::post('/employee/leave/store', [EmployeeController::class, 'storeLeave'])->name('employee.leave.store');
        Route::get('/employee/leave/history', [EmployeeController::class, 'leaveHistory'])->name('employee.leave.history');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
