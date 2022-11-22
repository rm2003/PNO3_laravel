<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;

class postrequests extends Controller
{
    function add(Request $req)
    {
        error_log(gettype($req)); 
        error_log($req);  
        #$test = json_decode(strval($req->all()), true);
        $reqContent = json_decode($req->getContent(), true);
        
        #error_log($test);
        $user= new Users;

        error_log($reqContent['UserId']);
        $user->UserId=$reqContent['UserId'];
        $user->email=$reqContent['email'];
        $user->licenseplate=$reqContent['licenseplate'];
        $hashed_password = password_hash($reqContent['password'], PASSWORD_DEFAULT);
        $user->password=$hashed_password;
        $result=$user->save();


        /*
        #$user->UserId= request()->user()->UserId;
        $user->UserId=$req->UserId;
        error_log($req->UserId);
        $user->email=$req->email;
        $user->licenseplate=$req->licenseplate;
        $user->password=$req->password;
        $result=$user->save();
        */

        if($result){
            return ["Result"=>"Data has been saved"];
        }
        else{
            return ["Result"=>"Operation failed"];
        }
    }
    
}
