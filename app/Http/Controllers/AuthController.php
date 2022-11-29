<?php

namespace App\Http\Controllers;

use App\Models\Users;
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
class AuthController extends Controller
{
    //Function which creates a token
    public function create_token($token_length)
    {
    //token creation from: https://gist.github.com/ursuleacv/80d35b6b6d13fc8760ca
    $t1 = microtime();
    function crypto_rand_secure($min, $max) {
            $range = $max - $min;
            if ($range < 0) return $min; // not so random...
            $log = log($range, 2);
            $bytes = (int) ($log / 8) + 1; // length in bytes
            $bits = (int) $log + 1; // length in bits
            $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
            do {
                $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
                $rnd = $rnd & $filter; // discard irrelevant bits
            } while ($rnd >= $range);
            return $min + $rnd;
    }
    
    function getToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ!?#@";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for($i=0;$i<$length;$i++){
            $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
        }
        return $token;
    }

    
    //Create the token
    $input['token'] = getToken($token_length);

    //check if the token is not already in the database
    $rules = array('token' => 'unique:access_tokens,token');
    $validator = Validator::make($input, $rules);

    //if token already in database, run the function again, if the token is unique, it gives the token back
    if ($validator->fails()) {
        return create_token($token_length);
    }
    else {
        return $input['token'];
    }

    
    }
    

    public function register(Request $request)
    {
        
        error_log("REGISTER");
        error_log($request);
        $user= new Users;

        error_log(gettype($request));
        $reqContent = json_decode($request->getContent(), true);

        $rules = [
            //'UserId' => 'required',
            'email' => 'required|string',
            'licenseplate' => 'required|string',
            'password' => 'required|string'    
            ];

        $validator = Validator::make($reqContent, $rules);

        if ($validator->passes() ) {
            if(Users::where('email', '=', $reqContent['email'])->exists() or Users::where('licenseplate', '=', $reqContent['licenseplate'])->exists() or strlen($reqContent['password'])<5 or strlen($reqContent['licenseplate']) != 9){
                error_log('Een van de parameters is niet correct');
                $response = [
                    'result' => "Email or licenseplate already in database",
                    'token' => "abc"
                ];

                return response($response, 405);
                }
            else
            {
                $user->UserId=1;
                $user->email=$reqContent['email'];
                $user->licenseplate=$reqContent['licenseplate'];
                $password = $reqContent['password'];
                $password_with_salt = $password . "3Iw54#yr" . $reqContent['email'];
                $hashed_password = password_hash($password_with_salt, PASSWORD_BCRYPT);
                $user->password=$hashed_password;
                error_log($user);
                $user->save();

                //This was how we made the token with sanctum
                //$token = $user->createToken($reqContent['email'])->plainTextToken; 
                
                $token = $this->create_token(64);
                $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)

                $accestoken = new acces_tokens;
                $accestoken->email = $reqContent['email'];
                $accestoken->token = $token;
                $accestoken->last_used_at = $date;
                $accestoken->created_at = $date;
                $accestoken->save(); 

                

                $response = [
                    'result' => "Registered successfully",
                    'token' => $token
                 ];
                
                 return response($response, 201);

        }}else{
            error_log('Een van de parameters is niet correct');
            $response = [
                'result' => "Something went wrong",
                'token' => "abc"
            ];

            return response($response, 405);
        }
    
    }

    public function login(Request $request)
    {
        error_log("LOGIN");
        error_log($request);
        error_log(gettype($request));
        $reqContent = json_decode($request->getContent(), true);

        //error_log($reqContent);

        $email = $reqContent['email'];
        $password = $reqContent['password'];

        $password_with_salt = $password . "3Iw54#yr" . $email;
        $hashed_password = password_hash($password_with_salt, PASSWORD_BCRYPT);

        $info_over_user = Users::where('email',$email)->get();
        error_log($info_over_user);


        if($info_over_user[0]["password"] == $hashed_password){

        $rules = [
                'email' => 'required|string',
                'password' => 'required|string'    
                ];
    
        $validator = Validator::make($reqContent, $rules);
        
        if ($validator->fails()) {
            $response = [
                'result' => "Pleas fill in all fields",
                'token' => "abc"
            ];

            return response($response, 400);
        }       


        $input['email'] = $email;    
        $rules = array('email' => 'unique:access_tokens, email');

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            error_log("User was still in database access_tokens");
            acces_tokens::where('email', $email)->delete();
        }


        $token = $this->create_token(64);
        $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)


        $accestoken = new acces_tokens;
        $accestoken->email = $reqContent['email'];
        $accestoken->token = $token;
        $accestoken->last_used_at = $date;
        $accestoken->created_at = $date;
        $accestoken->save(); 

        $response = [
            'result' => "Logged in successfully",
            'token' => $token
        ];
        return response($response, 200);

    }else{
        $response = [
            'result' => "Logged in failed: password is incorrect",
            'token' => "abc"
        ];
        return response($response, 401);

    }
    }


    public function logout(Request $request){
        error_log("LOGOUT");
        $reqContent = json_decode($request->getContent(), true);
        $email = $reqContent['email'];
        $input['email'] = $email;    
        $rules = array('email' => 'unique:access_tokens,email');

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            acces_tokens::where('email', $email)->delete();
            $response = ['result' => 'Logged out succesfully'];
            return response($response, 200);
        }
        else{
            $response = ['result' => 'You are already logged out'];
            return response($response, 400);
        }

    }

}
