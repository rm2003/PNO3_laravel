<?php

namespace App\Http\Controllers;

use App\Models\reservations;
use App\Models\checkedinlp;
use App\Models\history;

use Illuminate\Http\Request;
use Validator;


class parking_inout extends Controller
{
    public function driving_in(Request $request){
        error_log("DRIVING IN");
        $reqContent = json_decode($request->getContent(), true);

        //this are rules which the request needs to fullfil
        //to make sure all the fields were filled in
        error_log($reqContent["licenseplate"]);
        $rules = [
            'token' => 'required|string',
            'licenseplate' => 'required|string'    
            ];

        //the validator will checks the rules on the request
        $validator = Validator::make($reqContent, $rules);

        //if the validator fails, which means not all the necessary content for the request was there
        //sent back to please make a valid request
        if ($validator->fails()) {
            $response = [
                'result' => "Pleas make a valid request"
            ];

            return response($response, 400);
        }   

        $reference_token = "am(!@8eyVgdCtAGa367agIZ+&Z2^sFImH&Pb!jfLp2+ZUrDXT6cIs7yK&2tHb(XQ";

        if ($reqContent["token"] = $reference_token){
            $licenseplate = $reqContent["licenseplate"];
            if(strlen($licenseplate) == 9){

                if(checkedinlp::where('licenseplate', '=', $licenseplate)->exists()){
                    $response = [
                        'result' => "Licenseplate is already inside"
                    ];
                    return response($response);
                }
                if(reservations::where('licenseplate', '=', $licenseplate)->exists()){
                    //TODO:nog maken dat ook alleen kan als je gereserveerd hebt
                    //$info_about_user = Users::where('email', '=', $email)->get();
                    //$licenseplate = $info_about_user[0]['licenseplate'];

                    $entering = new checkedinlp;
                    $entering->licenseplate = $licenseplate;
                    $date = date('d-m-y H:i:s', strtotime('+ 1 hours')); //+1hour because date is in gmt, so plus 1 hour is our winter hour (time used when made)
                    $entering->time_entered = $date;
                    $entering->save();
                }
                $response = [
                    'result' => "Entering is allowed"
                ];
                return response($response, );
            }
        }
    }
}
