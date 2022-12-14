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
        error_log("CHECK AVAILIBILITY");
        //Decode the json request
        $reqContent = json_decode($request->getContent(), true);

        //rules to see if token, email and timestamp is in the json and is a string
        $rules = [
            'token' => 'required|string',
            'email' => 'required|string',
            'timestamp1' => 'required|string'
            //'timestamp2' => 'required|string',
            //'timestamp3' => 'required|string',
            //'timestamp4' => 'required|string',  
            //'timestamp5' => 'required|string'
            ];
        
        //validatet the rules on the request
        $validator = Validator::make($reqContent, $rules);

        //If the validator passes, which means the rules were okay, then go further
        //else say not all fields were filled in and ask to fill them in
        if ($validator->passes() ) {

            $token = $reqContent['token'];
            $email = $reqContent['email'];
            error_log($email);
            //checks if the token is still valid and only then gives back the request
            $token_validation = app('App\Http\Controllers\request_validation')->token_validation($token, $email);
            if($token_validation = "Request validated"){
                $timestamp1 = $reqContent['timestamp1'];
                error_log($timestamp1);
                //$timestamp2 = $reqContent['timestamp2'];
                //$timestamp3 = $reqContent['timestamp3'];
                //$timestamp4 = $reqContent['timestamp4'];
                //$timestamp5 = $reqContent['timestamp5'];

                //get the amount of reservations on the given timeslot
                $count1 = reservations::where('reservation_slot', '=', $timestamp1)->get()->count();
                error_log($count1);
                //$count2 = reservations::where('reservation_slot', '=', $timestamp2)->get()->count();
                //$count3 = reservations::where('reservation_slot', '=', $timestamp3)->get()->count();
                //$count4 = reservations::where('reservation_slot', '=', $timestamp4)->get()->count();
                //$count5 = reservations::where('reservation_slot', '=', $timestamp5)->get()->count();

                //Return the result
                $result = [
                    'result' => $token_validation,
                    'timestamp1' => $count1
                    //'timestamp2' => $count2,
                    //'timestamp3' => $count3,
                    //'timestamp4' => $count4,
                    //'timestamp5' => $count5
                ];
                return response($result, 200);
            }
            else {
                $result = [
                    'result' => $token_validation
                ];
                return respons($result, 401);
            }
        } else{
            error_log("Please fill in all fields");
            $result = [
                'result' => "Please fill in all fields"
            ];
            return response($result, 400);
        }
    }





    
    //the function for making a reservation
    public function reserve(Request $request){
        error_log($request);
        $reqContent = json_decode($request->getContent(), true);
        
        //set rules to check if all the the required 
        $rules = [
            'token' => 'required|string',
            'email' => 'required|string',
            'begin_hour' => 'required|string',
            'begin_day' => 'required|string',
            'begin_month' => 'required|string',
            'begin_year' => 'required|string',  
            'end_hour' => 'required|string',  
            'end_day' => 'required|string',      
            'end_month' => 'required|string',  
            'end_year' => 'required|string',  
            ];

        $validator = Validator::make($reqContent, $rules);

        if ($validator->passes() ) {

            $token = $reqContent['token'];
            $email = $reqContent['email'];

            $token_validation = app('App\Http\Controllers\request_validation')->token_validation($token, $email);
            if($token_validation = "Request validated"){
                $begin_hour = $reqContent['begin_hour'];
                $begin_day = $reqContent['begin_day'];
                $begin_month = $reqContent['begin_month'];
                $begin_year = $reqContent['begin_year'];

                $end_hour = $reqContent['end_hour'];
                $end_day = $reqContent['end_day'];
                $end_month = $reqContent['end_month'];
                $end_year = $reqContent['end_year'];

                
                $date_start = strtotime("$begin_year-$begin_month-$begin_day $begin_hour:00:00");
                $date_end = strtotime("$end_year-$end_month-$end_day $end_hour:00:00");
                $diff_hours = ($date_end - $date_start)/(3600);
                error_log($diff_hours);

                $list_with_dates = array();

                $date_for_dividation = "$begin_day-$begin_month-$begin_year $begin_hour:00:00";
                array_push($list_with_dates, "$begin_day-$begin_month-$begin_year $begin_hour");
                $diff_hours = $diff_hours - 1;
                
                error_log($date_for_dividation);
                
                //every hour between start and end date will be set in an array ($list_with_dates)
                while($diff_hours >= 1){    

                    //we will work with 2 types, 
                    //date for dividation is where is worked with, this is because strtotime +hour needs the minutes to see the hour as hour
                    //but we will not store in de database the minutes, only the hour, therefore is worked with the variable data for database
                    $date_for_dividation = date('d-m-Y H:i:s', strtotime($date_for_dividation. ' + 1 hours'));
                    $date_for_database = date('d-m-Y H', strtotime($date_for_dividation));
                    error_log("date for dividation");
                    error_log($date_for_dividation);
                    error_log("date for database");
                    error_log($date_for_database);
                    //the dates will be added to the array
                    array_push($list_with_dates, $date_for_database);
                    $diff_hours = $diff_hours - 1;
                
                }
                error_log(gettype($list_with_dates));
            

                //this is the max places in the parking lot
                $max_places = 3;

                //first there will be checked that there is still place availible in the parkin lot
                //if not give back that there is no place in one or more of the timeslots
                foreach($list_with_dates as $datum){
                    if(reservations::where('reservation_slot', '=', $datum)->get()->count() >= $max_places){
                        $result = [
                            'result' => "one or more timeslots not available"
                        ];
                        error_log("One or more timeslots not abailable");
                        return response($result);
                    }
                }

                //to add the licenseplate to a registration, we will get the licenseplate out of the users database
                $info_about_user = Users::where('email', '=', $email)->get();
                $licenseplate = $info_about_user[0]['licenseplate'];

                //for each hour between the start and end date (which is in the array), there will be a reservation made in the database
                foreach($list_with_dates as $timeslot){
                    $reservation = new reservations;
                    $reservation->email = $email;
                    $reservation->licenseplate = $licenseplate;
                    $reservation->reservation_slot = $timeslot;
                    $reservation->save();
                }

                //to let the user know the reservation is done, sent the confirmation back
                $result = [
                    'result' => "reservation successfully made"
                ];
                error_log("reservation successfully made");
                return response($result, 200);
        
            } else {
                $result = [
                    'result' => $token_validation
                ];
                error_log("probleem met token");
                return respons($result, 401);
            }
        } else{
            $result = [
                'result' => "Please fill in all fields"
            ];
            error_log("please fill in all fields");
            return response($result, 400);
        }

    }
}
