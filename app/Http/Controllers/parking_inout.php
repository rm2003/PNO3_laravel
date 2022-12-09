<?php

namespace App\Http\Controllers;

use App\Models\reservations;
use App\Models\checkedinlp;
use App\Models\history;
use App\Models\Users;

use Illuminate\Http\Request;
use Validator;


class parking_inout extends Controller
{
    public function driving_in(Request $request){
        error_log("DRIVING IN");
        $reqContent = json_decode($request->getContent(), true);
        error_log(gettype($request));
        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        //error_log($reqContent["token"]);
        $rules = [
            'token' => 'required|string',
            'licenseplate' => 'required|string'    
            ];
        error_log("plaats-1");
        //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);
        error_log("plaats0");
        //if the validator fails, which means not all the necessary content for the request was there
        //sent back to please make a valid request
        
        if ($validator->fails()) {
            $response = [
                'result' => "Please make a valid request"
            ];
            error_log("Please make a valid request");
            return response($response, 400);
        }   
        error_log("plaats1");
        $reference_token_in = "am(!@8eyVgdCtAGa367agIZ+&Z2^sFImH&Pb!jfLp2+ZUrDXT6cIs7yK&2tHb(XQ";

        if ($reqContent["token"] = $reference_token_in){
            error_log("plaats2");
            $licenseplate = $reqContent["licenseplate"];
            error_log($licenseplate);
            
            error_log("plaats3");
            if(checkedinlp::where('licenseplate', '=', $licenseplate)->exists()){
                $response = [
                    'result' => "Licenseplate is already inside"
                ];
                error_log("Licenseplate is already inside");
                return response($response);
            }
            if(reservations::where('licenseplate', '=', $licenseplate)->exists()){
                //TODO:nog maken dat ook alleen kan als je gereserveerd hebt
                //$info_about_user = Users::where('email', '=', $email)->get();
                //$licenseplate = $info_about_user[0]['licenseplate'];
                $date_for_check = date('d-m-Y H', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)
                error_log($date_for_check);
                if(reservations::where('licenseplate', '=', $licenseplate)->where('reservation_slot', '=', $date_for_check)->exists()){
                    $entering = new checkedinlp;
                    $entering->licenseplate = $licenseplate;
                    $date = date('d-m-Y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)
                    $entering->time_entered = $date;
                    $entering->save();
                        
                    $response = [
                        'result' => "Entering is allowed"
                    ];

                    error_log("Entering is allowed");
                    return response($response, 202); 

                } else{
                    $response = [
                        'result' => "Entering is not allowed"
                    ];
                        
                    error_log("You have not reserved on this timeslot");
                    return response($response, 403); 
                }
                    
                }else{
                    $response = [
                        'result' => "Entering is not allowed"
                    ];
                    
                    error_log("Entering not is allowed, please reserve first");
                    return response($response, 403); 
            } 
        
        }else{

            $response = [
                'result' => "Unauthorized"
            ];
            error_log("Unauthorized");
            return response($response, 401); 
        }
    }


    public function driving_out(Request $request){
        error_log("DRIVING OUT");
        $reqContent = json_decode($request->getContent(), true);
        error_log(gettype($request));
        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        //error_log($reqContent["token"]);
        $rules = [
            'token' => 'required|string',
            'licenseplate' => 'required|string'    
            ];
        error_log("plaats-1");
        //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);
        error_log("plaats0");
        //if the validator fails, which means not all the necessary content for the request was there
        //sent back to please make a valid request
        
        if ($validator->fails()) {
            $response = [
                'result' => "Please make a valid request"
            ];
            error_log("Please make a valid request");
            return response($response, 400);
        }   
        error_log("plaats1");
        $reference_token_out = "+pF!H*EmsnRj!Ft3MTB)NtK2(fzjnMSY&Tpr8gPPB#rU+tN+u&jtfwV4chW65KH";

        if ($reqContent["token"] = $reference_token_out){
            error_log("plaats2");
            $licenseplate = $reqContent["licenseplate"];
            error_log($licenseplate);
            error_log("plaats3");

            if(checkedinlp::where('licenseplate', '=', $licenseplate)->exists()){
                error_log("plaats4");
                $info_about_entering = checkedinlp::where('licenseplate', '=', $licenseplate)->get();
                $time_entered = $info_about_entering[0]["time_entered"];
                $time_left = date('d-m-Y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)
                    
                $info_about_user = Users::where('licenseplate', '=', $licenseplate)->get();
                $email = $info_about_user[0]['email'];
                
                $history = new history;
                $history->email = $email;
                $history->licenseplate = $licenseplate;
                $history->time_entered = $time_entered;
                $history->time_left = $time_left;
                //TODO: price generator + extra fee if overtime etc
                //With multiple prices?
                //$hourdiff = round((strtotime($time_left) - strtotime($time_entered))/3600, 1);
                $hourdiff = round((strtotime($time_left) - strtotime($time_entered))/3600,0.01);
                error_log($hourdiff);
                $price_per_hour = 5;
                $history->price = $hourdiff * $price_per_hour;
                $history->payed = "NO";
                $history->save();


                checkedinlp::where('licenseplate', '=', $licenseplate)->delete();

                $response = [
                    'result' => "Leaving is allowed"
                ];
                error_log("Leaving is allowed");
                return response($response, 202); 

            }else{
                $response = [
                    'result' => "Licenseplate was not inside"
                ];
                error_log("Licenseplate was not inside");
                return response($response, 409); 
            }
                
        }else{

            $response = [
                'result' => "Unauthorized"
            ];
            error_log("Unauthorized");
            return response($response, 401); 
        }
    }

}
