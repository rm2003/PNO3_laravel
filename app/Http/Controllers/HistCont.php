<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\history;

use Validator;

class HistCont extends Controller
{
    public function get_history(Request $request){
        $reqContent = json_decode($request->getContent(), true);

        $rules = [
            'email' => 'required|string',
            'token' => 'required|string'    
        ];

        $validator = Validator::make($reqContent, $rules);
        
        if ($validator->fails()) {
            $response = [
                'result' => "Please make a valid request"
            ];
            error_log("Please fill in all fields ");
            return response($response, 400);
        }

        $token = $reqContent['token'];
        $email = $reqContent['email'];

        $token_validation = app('App\Http\Controllers\request_validation')->token_validation($token, $email);
        if($token_validation = "Request validated"){
            $list_histories_users = history::where('email', '=', $email)->get();
            
            $result = [
                'result' => $list_histories_users
            ];
            error_log($list_histories_users);
            
            return response($result, 202);

        } else{

            $result = [
                'result' => $token_validation
            ];
            return respons($result, 401);
        }

    }
}
