<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraCreditoController;
use App\Http\Controllers\RecarregamentoController;
use App\Http\Controllers\MensagemController;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

//Chamar View de Login
Route::get('/logar', [LoginController::class, 'index'])->name('login');

// Fazer Autenticacao
Route::post('/autenticar', [LoginController::class, 'login'])->name('fazer.login');
//Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Auth::routes();

// Rotas que exigem Autenticacao
Route::middleware(['auth'])->group(function () {

    //Dashbord
    Route::get('/home', [DashbordController::class, 'index'])->name('home.index');

    //User
    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::get('/client', [UserController::class, 'indexClient'])->name('client.index');
    // Alterar Senha
    Route::get('/alterar/senha', [UserController::class, 'senhaIndex'])->name('senha.index');
    Route::post('/editar/senha', [UserController::class, 'senhaUpdate'])->name('senha.update');

    Route::get('/register/user', [UserController::class, 'create'])->name('user.create');
    Route::post('/register/user', [UserController::class, 'store'])->name('user.store');

    //Area de SMS Cliente
    Route::get('/listar/clientes', [ClienteController::class, 'index'])->name('cliente.index'); 
    Route::get('/registar/cliente', [ClienteController::class, 'create'])->name('cliente.create'); 
    Route::post('/registar/clientes', [ClienteController::class, 'store'])->name('cliente.store');
    Route::get('/editar/cliente/{id}', [ClienteController::class, 'edit'])->name('cliente.edit');
    Route::get('/remover/cliente/{id}', [ClienteController::class, 'delete'])->name('cliente.delete');

    //Compra SMS
    Route::get('/listar/compras', [CompraCreditoController::class, 'index'])->name('compras.index'); 

    //Compra Recarregamento por parte de INOFRDATA
    Route::get('/listar/recarregamento', [RecarregamentoController::class, 'index'])->name('recarregamento.index'); 
    Route::get('/registar/recarregamento', [RecarregamentoController::class, 'create'])->name('recarregamento.create'); 
    Route::post('/registar/recarregamento', [RecarregamentoController::class, 'store'])->name('recarregamento.store');
    Route::get('/editar/recarregamento/{id}', [RecarregamentoController::class, 'edit'])->name('recarregamento.edit');

    //Mensagem 
    Route::get('/listar/mensagem', [MensagemController::class, 'index'])->name('mensagem.index');


});
