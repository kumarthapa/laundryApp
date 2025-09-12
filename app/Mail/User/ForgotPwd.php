<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Mail;
use App\Helpers\UtilityHelper;

class ForgotPwd extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public $user;

  public $subject = "Forgot Password";

  protected $mailParams;
  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct($user, $mail_params = array())
  {
    //
    $this->user = $user;
    $this->mailParams = $mail_params;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {

    $email_subject = (isset($this->mailParams['subject'])) ? $this->mailParams['subject'] : $this->subject;
    $this->from(
      //UtilityHelper::config('support_sender_email'),
      env('MAIL_FROM_ADDRESS'),
      env('MAIL_FROM_NAME')
      //UtilityHelper::config('sendername')
    )
      ->subject($email_subject)
      ->view('emails.user.forgotpwd')
      ->with([
        'user' => $this->user,
        'otp' => $this->mailParams['otp'],
      ]);
  }
}
