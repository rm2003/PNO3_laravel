<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\reservations;
use App\Models\acces_tokens;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Auth;

use Validator;
use App\Http\Controllers\AuthController;

class ResController extends Controller
{
    public function check_availibility(Request $request)
    {
        $reqContent = json_decode($request->getContent(), true);
        
        $token = $reqContent['token'];
        $email = $reqContent['email'];

        $token_validation = app('App\Http\Controllers\request_validation')->token_validation($token, $email);
        if($token_validation = "Request validated"){
            $timestamp1 = $reqContent['timestamp1'];
            $timestamp2 = $reqContent['timestamp2'];
            $timestamp3 = $reqContent['timestamp3'];
            $timestamp4 = $reqContent['timestamp4'];
            $timestamp5 = $reqContent['timestamp5'];

            $count1 = reservations::where('reservation_slot', '=', $timestamp1)->get()->count();
            $count2 = reservations::where('reservation_slot', '=', $timestamp2)->get()->count();
            $count3 = reservations::where('reservation_slot', '=', $timestamp3)->get()->count();
            $count4 = reservations::where('reservation_slot', '=', $timestamp4)->get()->count();
            $count5 = reservations::where('reservation_slot', '=', $timestamp5)->get()->count();

            $result = [
                'timestamp1' => $count1,
                'timestamp2' => $count2,
                'timestamp3' => $count3,
                'timestamp4' => $count4,
                'timestamp5' => $count5
            ];
            return response($result);
        }


    }
}
