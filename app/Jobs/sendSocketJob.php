<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class sendSocketJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $data) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void {
        logger('Here,Socket Queue works');

        try {

            Http::post(env('SOCKET_URL', 'http://localhost:3010') . '/emit', $this->data);
        } catch (\Exception $e) {
            logger($e);
        }
    }
}
