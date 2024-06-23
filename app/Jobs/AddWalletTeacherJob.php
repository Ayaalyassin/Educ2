<?php

namespace App\Jobs;

use App\Models\TeachingMethod;
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
    }
}
