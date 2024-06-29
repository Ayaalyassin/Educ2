<?php

namespace App\Jobs;

use App\Models\TeachingMethod;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddWalletTeacherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $teaching_method_id;
    public function __construct($teaching_method_id)
    {
        $this->teaching_method_id=$teaching_method_id;
    }


    public function handle(): void
    {
        $teaching_method=TeachingMethod::find($this->teaching_method_id);
        $profile_teacher=$teaching_method->profile_teacher()->first();
        $user=$profile_teacher->user()->first();
        $wallet=$user->wallet()->first();
        $wallet->update([
            'value'=>$wallet->value+$teaching_method->price
        ]);
        $notDeductedReservations = $teaching_method->reservation_teaching_methods()->where('deducted', 0)->count();

        if($notDeductedReservations==10 && $teaching_method->price>0)
        {
            $deducted=$teaching_method->price*0.1;
            $wallet->update([
                'value' => $wallet->value-$deducted
            ]);

            $admin=User::find(1);
            $wallet_admin=$admin->wallet()->first();
            $wallet_admin->update(['value'=>$wallet_admin->value+$deducted]);
            $teaching_method->reservation_teaching_methods()
                ->where('deducted',0)
                ->update(['deducted' => 1]);
        }
    }
}
