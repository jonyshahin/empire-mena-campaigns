<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(125);

        Queue::before(function (JobProcessing $event) {
            Log::info('🟢 Job starting: ' . $event->job->resolveName());
        });

        Queue::after(function (JobProcessed $event) {
            Log::info('✅ Job finished: ' . $event->job->resolveName());
        });

        Queue::failing(function (JobFailed $event) {
            Log::error('❌ Job failed: ' . $event->job->resolveName() . ' — ' . $event->exception->getMessage());
        });
    }
}
