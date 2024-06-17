<?php

namespace App\Jobs;

use App\Mail\CodeEmail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class sendCodeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mailData,$exist;

    public function __construct($mailData,$exist)
    {
        $this->mailData=$mailData;
        $this->exist=$exist;
    }


    public function handle(): void
    {
        Mail::to($this->exist->email)->send(new CodeEmail($this->mailData));
        $job=(new DeleteCodeJob($this->exist))->delay(Carbon::now()->addMinutes(2));
        $this->dispatch($job);
    }
}
