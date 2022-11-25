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
            
        }


    }
}
