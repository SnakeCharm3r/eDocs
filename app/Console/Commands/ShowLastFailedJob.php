<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowLastFailedJob extends Command
{
    protected $signature = 'queue:failed:show {--id= : Show specific failed job by ID}';

    protected $description = 'Show the exception for the most recent (or specified) failed queue job';

    public function handle(): int
    {
        $id = $this->option('id');

        $job = $id
            ? DB::table('failed_jobs')->where('id', $id)->first()
            : DB::table('failed_jobs')->orderByDesc('failed_at')->first();

        if (!$job) {
            $this->info('No failed jobs found.');
            return 0;
        }

        $this->line('Failed job ID: ' . $job->id);
        $this->line('UUID: ' . $job->uuid);
        $this->line('Connection: ' . $job->connection);
        $this->line('Queue: ' . $job->queue);
        $this->line('Failed at: ' . $job->failed_at);
        $this->newLine();
        $this->line('Exception:');
        $this->line('---');
        $this->line($job->exception);
        $this->line('---');

        return 0;
    }
}
