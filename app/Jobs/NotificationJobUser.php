<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotificationJobUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user,$title,$body;
    public function __construct($user,$title,$body)
    {
        $this->title=$title;
        $this->body=$body;
        $this->user=$user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::table('notifications')->insert([
            'title' => $this->title,
            'body' => $this->body,
            'user_id' => $this->user->id,
            'created_at'=>Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
