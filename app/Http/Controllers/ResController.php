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
    //function that gets 5 dates+time and gives back how many reservations there are
    public function check_availibility(Request $request)
    {
        //Decode the json request
        $reqContent = json_decode($request->getContent(), true);
        
        $token = $reqContent['token'];
        $email = $reqContent['email'];

        //checks if the token is still valid and only then gives back the request
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
                'validation' => $token_validation,
                'timestamp1' => $count1,
                'timestamp2' => $count2,
                'timestamp3' => $count3,
                'timestamp4' => $count4,
                'timestamp5' => $count5
            ];
            return response($result);
        }
        else {
            $result = [
                'validation' => $token_validation
            ];
            return respons($result);
        }
    }

    public function reserve(Request $request){
        error_log($request);
        $reqContent = json_decode($request->getContent(), true);
        
        $token = $reqContent['token'];
        $email = $reqContent['email'];

        $token_validation = app('App\Http\Controllers\request_validation')->token_validation($token, $email);
        if($token_validation = "Request validated"){
            $begin_uur = $reqContent['begin_uur'];
            $begin_dag = $reqContent['begin_dag'];
            $begin_maand = $reqContent['begin_maand'];
            $begin_jaar = $reqContent['begin_jaar'];

            $eind_uur = $reqContent['eind_uur'];
            $eind_dag = $reqContent['eind_dag'];
            $eind_maand = $reqContent['eind_maand'];
            $eind_jaar = $reqContent['eind_jaar'];



            $date_start = strtotime("$begin_jaar-$begin_maand-$begin_dag $begin_uur:00:00");
            $date_end = strtotime("$eind_jaar-$eind_maand-$eind_dag $eind_uur:00:00");
            $diff_hours = ($date_end - $date_start)/(3600);
            error_log($diff_hours);

            $list_with_dates = array();

            while($diff_hours >= 1){
                $diff_hours = $diff_hours - 1;
                $begin_uur = $begin_uur+1;

                if($begin_uur == 24){

                }

                array_push($list_with_dates,"$begin_dag-$begin_maand-$begin_jaar $begin_uur");
                
            }
            error_log(gettype($list_with_dates));
            
            foreach($list_with_dates as $datum){
                if(reservations::where('reservation_slot', '=', $datum)->get()->count() >= 5){
                    $result = [
                        'response' => "one or more timeslots not available"
                    ];
                    return response($result);
                }
            }

            $info_about_user = Users::where('email', '=', $email)->get();
            $licenseplate = $info_about_user[0]['licenseplate'];
            
            foreach($list_with_dates as $timeslot){

            $reservation = new reservations;
            $reservation->email = $email;
            $reservation->licenseplate = $licenseplate;
            $reservation->reservation_slot = $timeslot;
            $reservation->save();

            }

            $result = [
                'response' => "reservation succesfully made"
            ];
            return response($result);
        
        } else {
            $result = [
                'response' => $token_validation
            ];
            return respons($result);
        }

    }
}
