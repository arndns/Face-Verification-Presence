<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OwnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function(){
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware( 'role:admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');    
        Route::get('/admin/data', [AdminController::class, 'getdata'])->name('admin.data'); 
        Route::get('/admin/create', [AdminController::class, 'create'])->name('admin.create'); 
        Route::post('/store', [AdminController::class, 'store'])->name('admin.store');
        Route::get('/admin/{user}/edit', [AdminController::class, 'editdata'])->name('admin.edit');   
        Route::put('/admin/{user}/update', [AdminController::class, 'update'])->name('admin.update'); 
        Route::delete('/users/{user}/delete', [AdminController::class, 'destroy'])->name('admin.delete');  
        Route::get('/admin/location', [LocationController::class, 'locindex'])->name('location.index');
        Route::get('/admin/location/create', [LocationController::class, 'create'])->name('location.create');
        Route::post('/admin/location/store', [LocationController::class, 'store'])->name('location.store');
        Route::get('/admin/location/{location}/edit', [LocationController::class, 'edit'])->name('location.edit');
        Route::patch('/admin/location/{location}/update', [LocationController::class, 'update'])->name('location.update');
        Route::delete('/admin/location/{location}/delete', [LocationController::class, 'destroy'])->name('location.delete'); 
    });
    
    Route::middleware( 'role:employee')->group(function () {
        Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');    
        Route::get('/employee/camera', [EmployeeController::class, 'webcam'])->name('employee.camera');    
    });

});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
