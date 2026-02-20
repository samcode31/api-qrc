<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\TermReport;
use Carbon\Carbon;

class PostTermReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reportYear;
    protected $reportTerm;

    /**
     * Create a new job instance.
     */
    public function __construct($reportYear, $reportTerm)
    {
        $this->reportYear = $reportYear;
        $this->reportTerm = $reportTerm;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = TermReport::where([
            ['year', $this->reportYear],
            ['term', $this->reportTerm]
        ])->first();

        if($record) 
        {
           $record->posted = 1; 
           $record->date_posted = Carbon::now();
           $record->save();
        }
    }
}
