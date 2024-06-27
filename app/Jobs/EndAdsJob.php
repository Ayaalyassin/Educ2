<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class EndAdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $ads;


    public function __construct($ads)
    {
        $this->ads=$ads;
    }


    public function handle(): void
    {
        $profile_teacher=$this->ads->profile_teacher()->first();
        $user=$profile_teacher->user()->first();

        DB::table('notifications')->insert([
            'title' => 'تم الاكتمال',
            'body' => 'تم اكتمال عدد الطلاب في الاعلان الخاص بك',
            'user_id' => $user->id,
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s')
        ]);

    }
}
