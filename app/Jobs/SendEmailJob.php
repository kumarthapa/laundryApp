<?php

namespace App\Jobs;

use App\Helpers\ConfigHelper;
use App\Models\Setting\Appconfig;
use App\Models\settings\Configsetting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
//use App\Mail\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class SendEmailJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $details;
  public $timeout = 0;
  protected $obj = null;

  public $tries = 3;

  // protected $smtpDetails;

  //protected $mailClass;
  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($details, $obj = null)
  {
    $this->details = $details;
    $this->obj  = $obj;

    // $this->smtpDetails = $smtpDetails;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {



    ### Generic method to send email default laravel way , picking smtp config from env variables
    $email = new $this->details['mailclass']($this->obj, $this->details);

    $smtpDetails = ConfigHelper::getSMPTDetails();

    try {
      if ($smtpDetails) {
        $config = $smtpDetails;
        Log::info("config:" . json_encode($config));
        Config::set(
          'mail.mailers.smtp.host',
          $config['host']
        );
        Config::set('mail.mailers.smtp.port', $config['port']);
        Config::set('mail.mailers.smtp.encryption', $config['encryption']);
        Config::set('mail.mailers.smtp.username', $config['username']);
        Config::set('mail.mailers.smtp.password', $config['password']);
        Config::set('mail.from.address', $config['from_address']);
        Config::set('mail.from.name', $config['from_name']);
        $mail  = Mail::to($this->details['email']);
        if (isset($this->details['cc']) && is_array($this->details['cc']))
          $mail = $mail->cc($this->details['cc']);
        if (isset($this->details['bcc']) && is_array($this->details['bcc']))
          $mail = $mail->bcc($this->details['bcc']);
        if (isset($this->details['attachment'])) {
          $attachmentPath = $this->details['attachment'];

          if (file_exists($attachmentPath)) {
            $email->attach($attachmentPath);
          } else {
            // logger('File does not exist: ' . $attachmentPath);
            Log::info('File does not exist: ' . $attachmentPath);
          }
        }
        Log::error("Error: Testttt");

        $mail->send($email);
      } else {
        throw new \Exception("SMTP Details not found");
      }
    } catch (\Exception $e) {
      // logger("Error:" . $e->getMessage() . " in " . __FILE__ . " @line " . __LINE__);
      // Log::error("Error:" . $e->getMessage() . " in " . $e->getFile() . " @line " . $e->getLine());
      Log::error("Error:" . $e);
    } finally {
      // if ($smtpDetails) {
      //   // Reset the mail configuration to the original values
      //   Config::set('mail.mailers.smtp.host', $defaultConfig['host']);
      //   Config::set('mail.mailers.smtp.port', $defaultConfig['port']);
      //   Config::set('mail.mailers.smtp.encryption', $defaultConfig['encryption']);
      //   Config::set('mail.mailers.smtp.username', $defaultConfig['username']);
      //   Config::set('mail.mailers.smtp.password', $defaultConfig['password']);
      // }
    }
  }
}