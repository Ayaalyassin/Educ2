<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotificationJobProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $profile,$title,$body;
    public function __construct($profile,$title,$body)
    {
        $this->title=$title;
        $this->body=$body;
        $this->profile=$profile;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user=$this->profile->user()->first();
        DB::table('notifications')->insert([
            'title' => $this->title,
            'body' => $this->body,
            'user_id' => $user->id,
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
