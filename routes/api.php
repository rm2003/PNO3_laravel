<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\postrequests;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResController;
use App\Http\Controllers\parking_inout;
use App\Http\Controllers\HistCont;
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
//Here are the incomming requests defined, first the name in the URL and sent to the correct controller, secondly


//Public routes
Route::post("login", [AuthController::class, 'login']);
Route::post("register", [AuthController::class, 'register']);
Route::post("logout", [AuthController::class, 'logout']);
Route::post("add", [postrequests::class, 'add']);
Route::post("check_availability", [ResController::class, 'check_availibility']);
Route::post("reserve", [ResController::class, 'reserve']);
Route::post("driving_in", [parking_inout::class, 'driving_in']);
Route::post("driving_out", [parking_inout::class, 'driving_out']);
Route::post("get_history", [HistCont::class, 'get_history']);




//Not used in the final design, but kept if want to improve in the future
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
//Protected routes
//routes in here are protected, if you want normal post request, you get 'unauthenticated' message back
//Route::group(['middleware' => ['auth:sanctum']], function () {
    
//    Route::post('/test', function () {

        //$test = Users::all();
        //$test = Users::orderBy('licenseplate', 'desc')->get();
        //$test = Users::where('UserId', '5')->get();
    
//        return "test";
        //return $test;
       // return view('test', );
//    });
    
    

//});
