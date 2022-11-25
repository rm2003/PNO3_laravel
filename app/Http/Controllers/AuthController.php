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

    $token_before_check = getToken($token_length);
    $input['token'] = getToken($token_length);

    
    $rules = array('token' => 'unique:acces_tokens,token');

    $validator = Validator::make($input, $rules);

    if ($validator->fails()) {
        return create_token($token_length);
    }
    else {
        return $input['token'];
    }

    
    }
    

    public function register(Request $request)
    {
        
        
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
                $hashed_password = $reqContent['password'];
        //password_hash($reqContent['password'], PASSWORD_DEFAULT);
                $user->password=$hashed_password;
                error_log($user);
                $user->save();

                //$token = $user->createToken($reqContent['email'])->plainTextToken;
                 
                
                $token = $this->create_token(64);
                $date = date('d-m-y h:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)

                $accestoken = new acces_tokens;
                $accestoken->email = $reqContent['email'];
                $accestoken->token = $token;
                $accestoken->last_used_at = $date;
                $accestoken->created_at = $date;
                $accestoken->save(); 

                

                $response = [
                'result' => "Registered Succesfully",
                'token' => $token
                 ];
                
                 return response($response, 201);

        }}else{
            //dd($validator->errors()->all());
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
        error_log($request);
        error_log(gettype($request));
        $reqContent = json_decode($request->getContent(), true);

        //error_log($reqContent);

        $email = $reqContent['email'];
        $password = $reqContent['password'];
        $hashed_password = $password;

        $info_over_user = Users::where('email',$email)->get();
        //error_log($info_over_user);
        if(error_log($info_over_user[0]["password"]) == $hashed_password){

        $rules = [
                //'UserId' => 'required',
                'email' => 'required|string',
                //'licenseplate' => 'required|string',
                'password' => 'required|string'    
                ];
    
        $validator = Validator::make($reqContent, $rules);
        
        //$user = Users::where('email' , $email)->first();
        $user = Users::where('email', $reqContent['email'])->first();
        //$user->save;
        //error_log($user);
        //error_log($user);
        //$user->save();
        //$user = Auth::user();
        //$token = $user->createToken('d')->plainTextToken;

        //$user->tokens()->delete();
        //$user->revoke();
        
        $user->tokens()->where('name', $reqContent['email'])->delete();
        
        
        $token = $user->CreateToken($reqContent['email'])->plainTextToken;
        
        $response = [
            'result' => "Logged in Succesfully",
            'token' => $token
        ];
        return response($response, 201);

    }else{
        $response = [
            'result' => "Logged in failed",
            'token' => "abc"
        ];
        return response($response, 201);

    }
    }
}
