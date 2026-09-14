<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\LoginController;


Route::get('/', function () {
    return view('welcome');
});


//Chamar View de Login
Route::get('/login', [DashbordController::class, 'index'])->name('login');

//Chamar View de Login
Route::get('/registar', [DashbordController::class, 'registar'])->name('registar');

// Fazer Autenticacao
Route::post('/autenticar', [LoginController::class, 'login'])->name('fazer.login');
//Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Auth::routes();

// Rotas que exigem Autenticacao
Route::middleware(['auth'])->group(function () {

    //Dashbord
    Route::get('/home', [DashbordController::class, 'index'])->name('home.index');

});
