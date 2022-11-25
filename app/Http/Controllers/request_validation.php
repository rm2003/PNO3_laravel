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



class request_validation extends Controller
{
    public function token_validation($token, $email){
        error_log("Zit in request_validation");
        $input['email'] = $email;    
        
        $rules = array('email' => 'unique:access_tokens,email');
    
        $validator = Validator::make($input, $rules);
    
        if ($validator->fails()) {
            $user_data = acces_tokens::where('email', '=', $email)->get();
            error_log($user_data);
            if($user_data[0]["token"] == $token){
                
                
                $max_time_without_interaction = 7; //after a week without interaction you have to login again
                $lifetime_token = 30; //each month you have to login again
                
                $last_used_at = $user_data[0]["last_used_at"];
                $time_created = $user_data[0]["created_at"];

                $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)
                error_log($date);
                $since_last_usage =  ceil( abs(strtotime($date) - strtotime($last_used_at))/ (1000 * 60 * 60 * 24));
                
                $since_created = ceil(abs(strtotime($date) - strtotime($time_created)) / (1000 * 60 * 60 * 24));
                
               if($since_last_usage < $max_time_without_interaction && $since_created < $lifetime_token){
                    //hier als het binnen de tijd is
                    error_log("token nog geldig");
                    acces_tokens::where('email', '=', $email)->update(['last_used_at' => $date]);
                    return "Request validated";
                    
               }else{
                    //hier als het niet oke is
                    error_log("token is niet meer geldig");
                    return "Token expired, please log in again";

               }
                


                
            }
            else{
                return "Unauthenticated";
            }

            
        }
        else {
            return "Not logged in";
        }

    }
}
