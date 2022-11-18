<?php

use Illuminate\Support\Facades\Route;
use App\Models\Users;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {

    $test = Users::all();
    //$test = Users::orderBy('licenseplate', 'desc')->get();
    //$test = Users::where('UserId', '5')->get();


    return $test;
   // return view('test', );
});