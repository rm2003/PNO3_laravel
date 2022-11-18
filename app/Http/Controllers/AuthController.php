<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Auth;

use Validator;

class AuthController extends Controller
{
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
                //niks
                //dd($validator->errors()->all());
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

                $token = $user->createToken($reqContent['email'])->plainTextToken;
                
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
        error_log($info_over_user);
        if(error_log($info_over_user[0]["password"]) == $hashed_password){

        
        
        //$user = Users::where('email' , $email)->first();
        $user = Users::find($email);
        error_log($user);
        //error_log($user);
        //$user->save();
        //$user = Auth::user();
        //$token = $user->createToken('d')->plainTextToken;


        $token = $user->createToken($email)->plainTextToken;
        
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
