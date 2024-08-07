<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait GeneralTrait
{


    public function returnError($errNum, $msg)
    {
        return response()->json([
            'status' => false,
            'errNum' => $errNum,
            'message' => $msg
        ],intval($errNum));
    }


    public function returnSuccessMessage($msg = "", $errNum = "200")
    {
        return response()->json([
            'status' => true,
            'errNum' => $errNum,
            'message' => $msg
        ],intval($errNum));
    }

    public function returnData($value, $msg = "successfully")
    {
        return response()->json([
            'status' => true,
            'errNum' => "200",
            'message' => $msg,
            'data' => $value
        ],200);
    }


    public function returnValidationError($code = "422", $validator)
    {
        return $this->returnError($code, $validator->errors());
    }



    function saveAnyFile($file, $folder)
    {
        try {
            $file_extension = $file->getClientOriginalExtension();
            $file_name = time() . rand() . '.' . $file_extension;
            $file->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in file save ");
        }
    }


    public function deleteFile($file)
    {

        try {
            if (\File::exists(public_path($file))) {
                unlink($file);
            }
            return null;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "This Image Not found");
        }
    }

    function saveImage($photo, $folder)
    {
        try {
            $file_extension = $photo->getClientOriginalExtension();
            $file_name = time() . rand() . '.' . $file_extension;
            $photo->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in image save ");
        }
    }

    function saveImageByName($photo, $folder,$name)
    {
        try {
            $file_extension = $photo->getClientOriginalExtension();
            $file_name = $name. '.' . $file_extension;
            $photo->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in image save ");
        }
    }
    public function deleteImage($photo)
    {

        try {
            if (\File::exists(public_path($photo))) {
                unlink($photo);
            }
        } catch (\Exception $ex) {
            throw new HttpResponseException($this->returnError($ex->getCode(), "This image Not found"));
        }
    }

    function saveVideo($video, $folder)
    {
        try {
            $file_extension = $video->getClientOriginalExtension();
            $file_name = time() . rand() . '.' . $file_extension;
            $video->move($folder, $file_name);
            return $folder . '/' . $file_name;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "Error in video save ");
        }
    }


    public function deleteVideo($video)
    {
        try {
            if (\File::exists(public_path($video))) {
                unlink($video);
            }
            return null;
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), "This video Not found");
        }
    }

    function deleteFolder($path)
    {
        try {
            if (\File::exists(public_path($path))) \File::deleteDirectory(public_path($path));
        } catch (\Exception $ex) {
            throw new HttpResponseException($this->returnError($ex->getCode(), "This Folder Not found"));
        }
    }


//    public function sendNotification($user_id,$message,$title)
//    {
//        $SERVER_KEY=env('FCM_SERVER_KEY');
//        $user=User::find($user_id);
//        $fcm=Http::acceptJson()->withToken($SERVER_KEY)
//            ->post('https://fcm.googleapis.com/fcm/send',
//            [
//                'to'=>$user->fcm_token,
//                'notification'=>
//                [
//                    'title'=>$title,
//                    'body'=>$message
//                ]
//            ]);
//        return json_decode($fcm);
//    }

//    public function sendNotificationMulti($user_ids,$message,$title)
//    {
//        $SERVER_KEY=env('FCM_SERVER_KEY');
//        $fcm_tokens=User::find($user_ids)->pluck('fcm_token);
//        $fcm=Http::acceptJson()->withToken($SERVER_KEY)
//            ->post('https://fcm.googleapis.com/fcm/send',
//            [
//                'registration_ids'=>$fcm_tokens,
//                'notification'=>
//                [
//                    'title'=>$title,
//                    'body'=>$message
//                ]
//            ]);
//        return json_decode($fcm);
//    }


      public function newFirebase($title,$body,$fcm_token)
      {
          try {

              $apiUrl = 'https://fcm.googleapis.com/v1/projects/educ-9319e/messages:send';
              $access_token = Cache::remember('access_token', now()->addHour(), function () use ($apiUrl) {
                  $credentialsFilePath = storage_path('app/fcm.json');
                  $client = new \Google_Client();
                  $client->setAuthConfig($credentialsFilePath);
                  $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
                  $client->refreshTokenWithAssertion();
                  $token = $client->getAccessToken();
                  return $token['access_token'];
              });

              $headers=["Authorization:Bearer $access_token",
                  'Content-Type:application/json'];
              $test_data=[
                  "title"=>$title,
                  "description"=>$body
              ];

//              $data['data']=$test_data;
//              $data['token']=$fcm_token;
//              $payload['message']=$data;
              $payload = [
                  "message" => [
                      "token" => $fcm_token,
                      "notification" => [
                          "title" => $title,
                          "body" => $body
                      ],
                      "data" => [
                          "extra_data" => "Your additional data here"
                      ]
                  ]
              ];
              $payload=json_encode($payload);
              $ch=curl_init();
              curl_setopt($ch,CURLOPT_URL,$apiUrl);
              curl_setopt($ch,CURLOPT_POST,true);
              curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
              $response = curl_exec($ch);
              $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
              curl_close($ch);
              if ($statusCode == 200) {
                  return response()->json([
                      'message' => 'Notification has been Sent'
                  ]);
              } else {
                  return $this->returnError($statusCode, $response);
              }


          }
          catch (\Exception $e)
          {
              return $this->returnError("500",$e->getMessage());
          }
      }

}
