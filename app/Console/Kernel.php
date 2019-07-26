<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\NotifyCommand'
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

      /*
    * schedule
    * ฟังก์ชันสำหรับตั้งเรียก command และเวลาที่ตั้งค่า เพื่อนำไป where หาใน db 
    * @author	Aphisit Sipalat 58160161
    * @Create Date 2562-01-29
    */
    protected function schedule(Schedule $schedule)
    {

            $schedule->command('command:notify', array('time'=>7))->weeklyOn(6, '1:00'); // every sunday on 08.00 of ums
            $schedule->command('command:notify', array('time'=>'day'))->dailyAt('10:00'); //every day on 17.00 of hr
            $schedule->command('command:notify', array('time'=>'week'))->cron('0 1 * * 6'); // every sunday of hr
            $schedule->command('command:notify', array('time'=>'month'))->monthlyOn(date('t'), '1:00');; //every month of hr

            //$schedule->command('command:notify', array('time'=>'day'))->everyMinute(); 
            //$schedule->command('command:notify', array('time'=>'week'))->everyMinute(); 
    
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
