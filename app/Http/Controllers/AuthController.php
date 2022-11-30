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
    

    //the function for registering a new account
    public function register(Request $request)
    {
        //This logs are to know a register request came in en wath the request was
        //Se we can see in the log files what's happening (mainly important while the development is in proces)
        error_log("REGISTER");
        error_log($request);
        error_log(gettype($request));

        //Because the request comes in as a json, the json needs to be decoded to see whats inside the json
        //also a error log to see the inside of the json
        $reqContent = json_decode($request->getContent(), true);
        error_log($reqContent);
        
        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        $rules = [
            'email' => 'required|string',
            'licenseplate' => 'required|string',
            'password' => 'required|string'    
            ];

        //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);

        //if the validator passes, which means all the fields were filled in, the if statement is correct
        //els there will be sent back that not all the fields were filled in
        if ($validator->passes() ) {
            //check if the email or licenseplate are not yet in the database
            //if already in the database, then sent back that it is already in the database
            if(Users::where('email', '=', $reqContent['email'])->exists() or Users::where('licenseplate', '=', $reqContent['licenseplate'])->exists()){
                error_log('Email or licenseplate already in the database');
                
                $response = [
                    'result' => "Email or licenseplate already in database",
                    'token' => "abc"
                ];

                return response($response, 405);

            } //check if the licenseplate is 9 characters long, else sent back
            elseif(strlen($reqContent['licenseplate']) != 9){
                error_log("The licensplate is not a valid one");
                $response = [
                    'result' => "Licensplate is not a valid one",
                    'token' => "abc"
                ];

                return response($response, 405);

            } //check that the password is long enough (longer than 5 characters)
            elseif(strlen($reqContent['password'])<5){
                error_log("The password is not long enough")
                $response = [
                    'result' => "Password is not long enough, please choos a longer one",
                    'token' => "abc"
                ];

                return response($response, 405);
            }//if all the checks passes put the user in the database
            else{
                //create a new user in the Users database
                $user= new Users;
                //User id here was introduced to immidiatly delete all the test records
                $user->UserId=1;
                $user->email=$reqContent['email'];
                $user->licenseplate=$reqContent['licenseplate'];
                $password = $reqContent['password'];
                //To store the password directly in the database is not safe
                //That is it is hashed, but before the has is added a unique salt, with a random set digits + email
                $password_with_salt = $password . "3Iw54#yr" . $reqContent['email'];
                $hashed_password = password_hash($password_with_salt, PASSWORD_BCRYPT);
                $user->password=$hashed_password;
                //log which user is put in the database
                error_log($user);
                //save the users to the database
                $user->save();

                //This was how we made the token with sanctum, but not used in final product
                //$token = $user->createToken($reqContent['email'])->plainTextToken; 
                
                //create a token
                $token = $this->create_token(64);
                //gets the actual date the token was created
                $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)

                //put the created accestoken in the database so the registered user is automatically loged in
                $accestoken = new acces_tokens;
                $accestoken->email = $reqContent['email'];
                $accestoken->token = $token;
                $accestoken->last_used_at = $date;
                $accestoken->created_at = $date;
                $accestoken->save(); 

                //if everything went correctly return that is was succesful with the token
                $response = [
                    'result' => "Registered successfully",
                    'token' => $token
                 ];
                
                 return response($response, 201);

        }}//if not all fields are filled in
         else{
            error_log('Please fill in all fields');
            $response = [
                'result' => "Please fill in all fields",
                'token' => "abc"
            ];

            return response($response, 405);
        }
    
    }

    //the function for logging in a user
    public function login(Request $request)
    {
        error_log("LOGIN");
        error_log($request);
        error_log(gettype($request));
        $reqContent = json_decode($request->getContent(), true);

        //error_log($reqContent);
        
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


        $email = $reqContent['email'];
        $password = $reqContent['password'];

        $password_with_salt = $password . "3Iw54#yr" . $email;
        $hashed_password = password_hash($password_with_salt, PASSWORD_BCRYPT);

        $info_over_user = Users::where('email',$email)->get();
        error_log($info_over_user);


        if(error_log($info_over_user[0]["password"]) == $hashed_password){            


        $input['email'] = $email;    
        $rules = array('email' => 'unique:access_tokens,email');

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
