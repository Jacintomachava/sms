<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/entrar', [AuthController::class, 'index'])->name('formulario');
Route::get('/registar', [AuthController::class, 'registar'])->name('registar');
Route::post('/fazer/register', [AuthController::class, 'login'])->name('login.store');

//DASHBORD
Route::get('/dashboard', [DashbordController::class, 'index'])->name('dashboard');

Auth::routes();

// Rotas que exigem Autenticacao
Route::middleware(['auth'])->group(function () {

    //Dashbord
    Route::get('/home', [DashbordController::class, 'index'])->name('home.index');

});
