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

    //$test = Users::all();
    //$test = Users::orderBy('licenseplate', 'desc')->get();
    //$test = Users::where('UserId', '5')->get();
    //$date = date('d-m-y h:i:s', strtotime('+ 1 hours'));
    //return $date;

    //$bytes = random_bytes(30);
    //return var_dump(bin2hex($bytes));





    //return $test;
   // return view('test', );
});
