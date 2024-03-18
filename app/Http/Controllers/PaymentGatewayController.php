<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentGatewayController extends Controller
{
     //get token
     public function getToken(){
        try{
         $client = new Client();
         $response = $client->request('POST', 'https://bluepaie.com/pay/api/v1/authentication/token', [
             'json' => [
                 'client_id' => "xmv9umVvElCzsFzw1AFPs89gmD9J1lpdGttyKkn1YuaUXDrg1jsVkvsjlkyZCoyZrAzO9EKlPfAf8n6TT1XXPmdQGkXqknhmPVoV", //Rempacez par le votre
                 'secret_id' =>   "1KAclzPw3BWGncpgmnDF4Ba8WdD9KTvP8J5K8VuS4nUJRimtOk2yCVZZeDTzhUpUPzj6mfpBnC9OpDgEsV6udjd9Q4qWTtHudU44",//Rempacez par le votre
             ],
             'headers' => [
                 'accept' => 'application/json',
                 'content-type' => 'application/json',
             ],
         ]);
         $result = json_decode($response->getBody(),true);
         $data = [
             'code' => $result['message']['code'],
             'message' =>  $result['type'],
             'token' => $result['data']['access_token']??"",

         ];
         return (object)$data;
        }catch(Exception $e){
         $errorMessage = $e->getMessage();
         $errorArray = [];
         if (preg_match('/{.*}/', $errorMessage, $matches)) {
             $errorArray = json_decode($matches[0], true);
         }
         $data = [
             'code' => $errorArray['message']['code'],
             'message' => $errorArray['message']['error'],
             'token' => '',

         ];
         return (object)$data;
        }
     }
     //payment initiate
     public function initiatePayment(Request $request){
         $validator = Validator::make($request->all(), [
             'amount'         => "required|string|max:60"
         ]);
         if ($validator->fails()) {
             return back()->withErrors($validator->errors())->withInput();
         }
         $validated =$validator->validate();
         $access_token_info = $this->getToken();
         if($access_token_info->code != 200){
             return back()->with(['error' => [$access_token_info->message[0]]]);
         }else{
             $access_token =   $access_token_info->token??'';
         }

         try{
             $client = new \GuzzleHttp\Client();
             $response = $client->request('POST', 'https://bluepaie.com/pay/api/v1/payment/create', [
                 'json' => [
                         'amount' =>     $validated['amount'],
                         'currency' =>   "USD", //Nous acceptons pour l'instant une seule devise (USD)
                         'return_url' =>     route('pay.success'),
                         'cancel_url' =>     route('pay.cancel'),
                         'custom' =>       $this->custom_random_string(10),
                     ],
                 'headers' => [
                     'Authorization' => 'Bearer '. $access_token,
                     'accept' => 'application/json',
                     'content-type' => 'application/json',
                     ],
             ]);
             $result = json_decode($response->getBody(),true);
             return redirect($result['data']['payment_url']);

         }catch(Exception $e){
             $errorMessage = $e->getMessage();
             $errorArray = [];
             if (preg_match('/{.*}/', $errorMessage, $matches)) {
                 $errorArray = json_decode($matches[0], true);
             }
             if(isset($errorArray['message']['error'][0])){
                 return back()->with(['error' => [ $errorArray['message']['error'][0]]]);
             }else{
                 return back()->with(['error' => ["Quelque chose a mal tourné, veuillez essayer ultérieurement"]]);
             }


         }

     }
     //after pay success
     public function paySuccess(Request $request){
         $getResponse = $request->all();
         if( $getResponse['type'] == 'success'){
            //write your needed code here
            return redirect()->route('pay.page')->with(['success' => ['Votre paiement a été effectué avec succès']]);
         }

     }
     //after cancel payment
     public function payCancel(Request $request){
         //write your needed code here
         return redirect()->route('pay.page')->with(['error' => ['Votre paiement a été annulé']]);
     }
     //custom transaction id which can use your project transaction
     function custom_random_string($length = 10) {
         $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
         $char_length = strlen($characters);
         $random_string = '';

         for ($i = 0; $i < $length; $i++) {
             $random_string .= $characters[rand(0, $char_length - 1)];
         }
         return $random_string;
     }
}
