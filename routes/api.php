<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\postrequests;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResController;
use App\Models\Users;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Public routes
Route::post("login", [AuthController::class, 'login']);
Route::post("register", [AuthController::class, 'register']);
Route::post("logout", [AuthController::class, 'logout']);
Route::post("add", [postrequests::class, 'add']);
Route::post("check_availibility", [ResController::class, 'check_availibility']);
Route::post("reserve", [ResController::class, 'reserve']);





//Protected routes
//routes in here are protected, if you want normal post request, you get 'unauthenticated' message back
Route::group(['middleware' => ['auth:sanctum']], function () {
    
    Route::post('/test', function () {

        //$test = Users::all();
        //$test = Users::orderBy('licenseplate', 'desc')->get();
        //$test = Users::where('UserId', '5')->get();
    
        return "test";
        //return $test;
       // return view('test', );
    });
    
    

});