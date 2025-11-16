<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FaceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/save-embedding', [FaceApiController::class, 'saveEmbedding']);


