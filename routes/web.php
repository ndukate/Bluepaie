<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentGatewayController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('pay-page');
})->name('pay.page');


Route::controller(PaymentGatewayController::class)->prefix('payment-gateway')->name('pay.')->group(function(){
    Route::get('get-token','getToken')->name('get.token');
    Route::post('initiate-payment','initiatePayment')->name('initiate.payment');
    Route::get('success','paySuccess')->name('success');
    Route::get('cancel','payCancel')->name('cancel');

});
