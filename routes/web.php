<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SenderIdController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\GrupoContactoController;
use App\Http\Controllers\ContactoImportController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\Admin\ContaController;
use App\Http\Controllers\Admin\CompraStockSmsController;
use App\Http\Controllers\Admin\TarifaSmsController;
use App\Http\Controllers\CompraSmsController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\Admin\SenderIdController as AdminSenderIdController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/entrar', [AuthController::class, 'index'])->name('formulario');
Route::get('/registar', [AuthController::class, 'registar'])->name('registar');
Route::post('/registar', [AuthController::class, 'store'])->name('register.store');
Route::post('/fazer/register', [AuthController::class, 'login'])->name('login.store');

Auth::routes();


Route::middleware(['auth','account.active'])->group(function () {


    Route::get('/dashboard', function () { return view('dashbord.index');})->name('dashboard');

    Route::get('/sms', [SmsController::class, 'index'])->middleware('account.permission:sms.view')->name('sms.index');
    Route::post('/sms/enviar', [SmsController::class, 'store'])->middleware('account.permission:sms.send')->name('sms.store');

    Route::get('/contactos',[ContactoController::class, 'index'])->name('contactos.index');
    Route::post('/contactos',[ContactoController::class, 'store'])->name('contactos.store');
    Route::put('/contactos/{id}', [ContactoController::class, 'update'])->name('contactos.update');
    Route::patch('/contactos/{id}/estado', [ContactoController::class, 'alterarEstado'])->name('contactos.estado');
    Route::delete('/contactos/{id}', [ContactoController::class, 'destroy'])->name('contactos.destroy');

    Route::get('/contactos/importacao/modelo', [ContactoImportController::class, 'modelo'])->name('contactos.importacao.modelo');
    Route::post('/contactos/importacao',[ContactoImportController::class, 'importar'])->name('contactos.importacao.importar');

    Route::get('/grupos-contactos',[GrupoContactoController::class, 'index'])->name('grupos-contactos.index');
    Route::post('/grupos-contactos',[GrupoContactoController::class, 'store'])->name('grupos-contactos.store');
    Route::put('/grupos-contactos/{id}', [GrupoContactoController::class, 'update'])->name('grupos-contactos.update');
    Route::patch('/grupos-contactos/{id}/estado', [GrupoContactoController::class, 'alterarEstado'])->name('grupos-contactos.estado');
    Route::delete('/grupos-contactos/{id}', [GrupoContactoController::class, 'destroy'])->name('grupos-contactos.destroy');

    Route::get('/sender-ids', [SenderIdController::class, 'index'])->middleware('account.permission:senders.view')->name('sender-ids.index');
    Route::post('/sender-ids', [SenderIdController::class, 'store'])->name('sender-ids.store');
    Route::get('/sender-ids', [SenderIdController::class, 'index'])->name('sender-ids.index');

    Route::post('/sender-ids/{sender}/enviar-operadora',[AdminSenderIdController::class, 'enviarOperadora'])->name('sender-ids.enviar-operadora');
    Route::post('/sender-ids/{sender}/aprovar',[AdminSenderIdController::class, 'aprovar'])->name('sender-ids.aprovar');
    Route::post('/sender-ids/{sender}/rejeitar',[AdminSenderIdController::class, 'rejeitar'])->name('sender-ids.rejeitar');
    Route::post('/sender-ids/{sender}/suspender',[AdminSenderIdController::class, 'suspender'])->name('sender-ids.suspender');
    Route::post('/sender-ids/{sender}/reactivar',[AdminSenderIdController::class, 'reactivar'])->name('sender-ids.reactivar');

    Route::get('/compras-sms',[CompraSmsController::class, 'index'])->name('compras.sms.index');
    Route::post('/compras-sms/calcular',[CompraSmsController::class, 'calcular'])->name('compras.sms.calcular');
    Route::post('/compras-sms/comprar',[CompraSmsController::class, 'comprar'])->name('compras.sms.comprar');
    Route::post('/compras-sms/{id}/pagar',[CompraSmsController::class, 'pagar'])->name('compras.sms.pagar');

    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    Route::get('/historico/sms', [SmsController::class, 'historico'])->name('sms.hitorico');
    Route::post('/sms/calcular',[SmsController::class, 'calcular'])->name('sms.calcular');
    Route::post('/sms/enviar',[SmsController::class, 'enviar'])->name('sms.enviar');

    Route::get('/integracao-api', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/integracao-api/keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::post('/integracao-api/keys/{id}/revoke', [ApiKeyController::class, 'revoke'])->name('api-keys.revoke');

    Route::get('/financeiro', [FinanceiroController::class, 'index'])->name('financeiro.index');

});


Route::middleware(['auth', 'platform'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/stock-sms',[CompraStockSmsController::class, 'index'])->name('stock.index');
    Route::post('/stock-sms/compras',[CompraStockSmsController::class, 'store'])->name('stock.store');

    Route::get('/dashboard', function () { return view('admin.dashboard'); })->name('dashboard');

    Route::get('/sender-ids',[AdminSenderIdController::class, 'index'])->name('sender-ids.index');
    Route::post('/sender-ids/{sender}/enviar-operadora',[AdminSenderIdController::class, 'enviarOperadora'])->name('sender-ids.enviar-operadora');
    Route::post('/sender-ids/{sender}/aprovar',[AdminSenderIdController::class, 'aprovar'])->name('sender-ids.aprovar');
    Route::post('/sender-ids/{sender}/rejeitar',[AdminSenderIdController::class, 'rejeitar'])->name('sender-ids.rejeitar');
    Route::post('/sender-ids/{sender}/suspender',[AdminSenderIdController::class, 'suspender'])->name('sender-ids.suspender');
    Route::post('/sender-ids/{sender}/reactivar',[AdminSenderIdController::class, 'reactivar'])->name('sender-ids.reactivar');

    Route::get('/sender-ids', [AdminSenderIdController::class,'index'])->name('sender-ids.index');
    Route::post('/sender-ids/{sender}/enviar-operadora',[AdminSenderIdController::class,'enviarOperadora'])->name('sender-ids.enviar-operadora');
    Route::post('/sender-ids/{sender}/aprovar',[AdminSenderIdController::class,'aprovar'])->name('sender-ids.aprovar');
    Route::post('/sender-ids/{sender}/rejeitar',[AdminSenderIdController::class,'rejeitar'])->name('sender-ids.rejeitar');
    Route::post('/sender-ids/{sender}/suspender', [AdminSenderIdController::class,'suspender'])->name('sender-ids.suspender');
    Route::post('/sender-ids/{sender}/reactivar', [AdminSenderIdController::class, 'reactivar'])->name('sender-ids.reactivar');

    Route::get('/tarifas-sms', [TarifaSmsController::class, 'index'])->name('tarifas-sms.index');
    Route::post('/tarifas-sms', [TarifaSmsController::class, 'store'])->name('tarifas-sms.store');
    Route::put('/tarifas-sms/{tarifa}',[TarifaSmsController::class, 'update'])->name('tarifas-sms.update');
    Route::patch('/tarifas-sms/{tarifa}/estado',[TarifaSmsController::class, 'alterarEstado'])->name('tarifas-sms.estado');

    Route::get('/contas', [ContaController::class, 'index'])->name('contas.index');
    Route::post('/contas', [ContaController::class, 'store'])->name('contas.store');

});