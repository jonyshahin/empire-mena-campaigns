<?php

namespace App\Jobs;

use App\Mail\ExportCompletedMail;
use App\Mail\ExportFailedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($email, $filePath)
    {
        $this->email = $email;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new ExportCompletedMail($this->filePath));
        Log::info("✅ [Export Completed] Email sent to {$this->email} with file: {$this->filePath}");
    }

    public function failed(\Throwable $exception)
    {
        Log::error('❌ Export Job failed: ' . $exception->getMessage());
        Mail::to($this->email)->send(new ExportFailedMail());
    }
}
