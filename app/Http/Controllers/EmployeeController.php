<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index() :View {
        $user = User::find(Auth::id());
        return view('Employee.index', compact('user'));
    }

    public function webcam(){
        return view('Employee.camera');
    }
}
