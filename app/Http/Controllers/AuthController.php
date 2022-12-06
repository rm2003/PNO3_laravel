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

        error_log("-1");
        //Because the request comes in as a json, the json needs to be decoded to see whats inside the json
        //also a error log to see the inside of the json
        $reqContent = json_decode($request->getContent(), true);
        error_log("test0");
        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        $rules = [
            'email' => 'required|string',
            'licenseplate' => 'required|string',
            'password' => 'required|string'    
            ];

        //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);
        
        error_log("test1");
        //if the validator fails, which means not all the fields were filled in,
        //sent back to please fill in all the fields
        if ($validator->fails()) {
            $response = [
                'result' => "Please fill in all fields",
                'token' => "abc"
            ];
            error_log("Please fill in all fields ");
            return response($response, 400);
        }   

        
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
            error_log("The password is not long enough");
            $response = [
                'result' => "Password is not long enough, please choos a longer one",
                'token' => "abc"
            ];

            return response($response, 405);
         }//if all the checks passes put the user in the database
          else{
            error_log("Starting with putting user in database");
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

            error_log("User succesfully added to database");
            //This was how we made the token with sanctum, but not used in final product
            //$token = $user->createToken($reqContent['email'])->plainTextToken; 
                
            //create a token
            $token = $this->create_token(64);
            //gets the actual date the token was created
            $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)

            error_log("Starting with putting token in database");
            //put the created accestoken in the database so the registered user is automatically loged in
            $accestoken = new acces_tokens;
            $accestoken->email = $reqContent['email'];
            $accestoken->token = $token;
            $accestoken->last_used_at = $date;
            $accestoken->created_at = $date;
            $accestoken->save(); 

            error_log("token successfully in database");
            //if everything went correctly return that is was succesful with the token
            $response = [
                'result' => "Registered successfully",
                'token' => $token,
                'email' => $reqContent['email']
            ];
                
            return response($response, 201);

        }
    }

    //the function for logging in a user
    public function login(Request $request)
    {
        //This logs are to know a login request came in en wath the request was
        //Se we can see in the log files what's happening (mainly important while the development is in proces)
        error_log("LOGIN");
        error_log($request);
        error_log(gettype($request));

        //Because the request comes in as a json, the json needs to be decoded to see whats inside the json
        $reqContent = json_decode($request->getContent(), true);
        
        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        $rules = [
            'email' => 'required|string',
            'password' => 'required|string'    
            ];

        
            //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);
    

        //if the validator fails, which means not all the fields were filled in
        //there will be sent back to Ples fill in all the fields
        if ($validator->fails()) {
            $response = [
                'result' => "Pleas fill in all fields",
                'token' => "abc"
            ];

            return response($response, 400);
        }   

        //after the checks, we know for sure all the fields were filled in
        //We parse the json
        $email = $reqContent['email'];
        $password = $reqContent['password'];

        //To store the password directly in the database is not safe
        //That is it is hashed, but before the has is added a unique salt, with a random set digits + email
        $password_with_salt = $password . "3Iw54#yr" . $email;
        $hashed_password = password_hash($password_with_salt, PASSWORD_BCRYPT);

        //Get all the info about the user, so we can later get the password
        $info_over_user = Users::where('email',$email)->get();
        error_log($info_over_user);

        if(error_log($info_over_user[0]["password"]) == $hashed_password){            

            //create a variable to check later some rules on it
            $input['email'] = $email;  

            //create a rule to see that the email is not in the database "access_tokens"  
            $rules = array('email' => 'unique:access_tokens,email');

            //the validator will validate the rules on the input
            $validator = Validator::make($input, $rules);

            //if the validator fails, which means the email was still in the table 
            //(user was still loged in, except for the lifetime of the token)
            //the user will be removed from the table
            if ($validator->fails()) {
                error_log("User was still in database access_tokens");
                acces_tokens::where('email', $email)->delete();
            }

            //create a token to sent it back so the user can later validate his request
            $token = $this->create_token(64);

            //get the actual time, to see when the token was created
            $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)

            //put the token in the database with the email address, time made and time last used
            $accestoken = new acces_tokens;
            $accestoken->email = $reqContent['email'];
            $accestoken->token = $token;
            $accestoken->last_used_at = $date;
            $accestoken->created_at = $date;
            $accestoken->save(); 

            //if everything went correct, return the token 
            $response = [
                'result' => "Logged in successfully",
                'token' => $token,
                'email' => $email
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

    //this is the function to log a user out
    public function logout(Request $request){
        //This logs are to know a logout request came in en wath the request was
        //Se we can see in the log files what's happening (mainly important while the development is in proces)
        error_log("LOGOUT");

        //because the request came in as a json, it needs to be decoded
        $reqContent = json_decode($request->getContent(), true);

        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        $rules = [
            'email' => 'required|string',
            'token' => 'required|string'   
            ];

        //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);
    
        //if the validator fails, which means not all the fields were filled in
        //there will be sent back to please fill in all the fields
        if ($validator->fails()) {
            error_log("It is not a valid request (not all parameters were filled in)");
            $response = [
                'result' => "Pleas make a vallid request",
                'token' => "abc"
            ];

            return response($response, 400);
        }
        
        //There's an email in the request so it will be parsed
        $email = $reqContent['email'];

        //create a variable to later check some rules on it
        $input['email'] = $email;    
        //define the rules which needed to be checked
        $rules = array('email' => 'unique:access_tokens,email');

        //Validate the rules on the variable
        $validator = Validator::make($input, $rules);

        //if the validator fails, which means the user was in the table
        //delete the token
        if ($validator->fails()) {
            acces_tokens::where('email', $email)->delete();
            $response = ['result' => 'Logged out succesfully'];
            return response($response, 200);
        }//else the user was not in the token table, so the user was not logged in
        else{
            $response = ['result' => 'You are already logged out'];
            return response($response, 400);
        }

    }

}
