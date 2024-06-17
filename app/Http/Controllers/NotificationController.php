<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use GeneralTrait;


    public function getAll(){
        try {
            $user = auth('api')->user();
            $notifications=$user->notifications()->orderBy('created_at','desc')->get();
            return $this->returnData($notifications,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }
    public function getNotificationsViewed(){
        try {
            $user = auth('api')->user();
            $notifications=$user->notifications()->where('seen',1)->get();
            return $this->returnData($notifications,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500", 'Please try again later');
        }
    }
    public function getNotificationsNotViewed(){
        try {
            $user = auth('api')->user();
            $notifications=$user->notifications()->where('seen',0)->get();
            return $this->returnData($notifications,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",'Please try again later');
        }
    }
    public function getById($id){
        try {
            $user = auth('api')->user();
            $notification=$user->notifications()->find($id);
            if (!$notification) {
                return $this->returnError("404", 'Not found');
            }
            $notification->update(['seen'=>1]);
            return $this->returnData($notification,'operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",'Please try again later');
        }
    }
    public function delete($id){
        try {
            $user = auth('api')->user();
            $notification=$user->notifications()->find($id);
            if (!$notification) {
                return $this->returnError("404", 'Not found');
            }
            $notification->delete();
            return $this->returnSuccessMessage('operation completed successfully');
        } catch (\Exception $ex) {
            return $this->returnError("500",'Please try again later');
        }
    }
}
