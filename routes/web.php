<?php

use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});
// Certificate verification page
Route::get('/verify/{code}', function($code) {
    $certificate = \App\Models\Certificate::where('verification_code', $code)
        ->with(['user', 'course'])
        ->firstOrFail();
    
    return view('certificates.verify', compact('certificate'));
})->name('verify.certificate');