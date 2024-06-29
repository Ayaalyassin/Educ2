<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateHoursStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hours:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the status of hours to 0 every midnight';

    public function handle()
    {
        
        $today = Carbon::now('Asia/Damascus')->locale('ar')->dayName;
        $days = DB::table('calendar_days')->where('day', $today)->get();

        if ($days->isNotEmpty()) {
            foreach ($days as $day) {
                DB::table('calendar_hours')
                    ->where('day_id', $day->id)
                    ->update(['status' => 0]);
            }
            $this->info('Hours status updated successfully!');
        } else {
            $this->info('No day found for today.');
        }

        return 0;
    }
}
