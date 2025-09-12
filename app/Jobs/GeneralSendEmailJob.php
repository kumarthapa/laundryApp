<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\CommonSendMail; // Import your Mailable class

class GeneralSendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set the mail configuration
        config([
            'mail.mailers.smtp.transport' => config('MAIL_MAILER', 'smtp'),
            'mail.mailers.smtp.host' => config('MAIL_HOST', 'sandbox.smtp.mailtrap.io'),
            'mail.mailers.smtp.port' => config('MAIL_PORT', 587),
            'mail.mailers.smtp.username' => config('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => config('MAIL_PASSWORD'),
            'mail.mailers.smtp.encryption' => config('MAIL_ENCRYPTION', 'tls'),
            'mail.from.address' => config('MAIL_FROM_ADDRESS'),
            'mail.from.name' => config('MAIL_FROM_NAME', env('APP_NAME')),
        ]);

        // Log the mail configuration for debugging
        $mailConfig = [
            'MAIL_MAILER' => config('MAIL_MAILER'),
            'MAIL_HOST' => config('MAIL_HOST'),
            'MAIL_PORT' => config('MAIL_PORT'),
            'MAIL_USERNAME' => config('MAIL_USERNAME'),
            'MAIL_PASSWORD' => config('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => config('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => config('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => config('MAIL_FROM_NAME'),
        ];

        Log::info('Mail configuration:', $mailConfig);

        // Create the Mailable instance
        $mailable = new CommonSendMail($this->data);

        // Check if the mailable's build method is valid before sending the email
        if ($mailable->build()) {
            Mail::to($this->data['email'])->send($mailable); // Send email if the build method is valid
            Log::info('Email sent to: ' . $this->data['email']);
        } else {
            Log::info('Email not sent. Invalid email data or type.');
        }
    }
}
