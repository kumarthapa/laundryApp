<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class CommonSendMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public $data;

  /**
   * Create a new message instance.
   *
   * @param array $data
   */
  public function __construct(array $data)
  {
    $this->data = $data;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    if ($this->data['type'] == 'placing_vehicle') {
      return $this->view('emails.placing_vehicle') // The view file for the email content
        ->with('data', $this->data) // Pass the data to the view
        ->subject($this->data['subject']); // Dynamic subject line
    } elseif ($this->data['type'] == 'supplier_vehicle_detail') {
      return $this->view('emails.supplier_vehicle_detail') // The view file for the email content
        ->with('data', $this->data) // Pass the data to the view
        ->subject($this->data['subject']); // Dynamic subject line
    } elseif ($this->data['type'] == 'employee_registration' || $this->data['type'] == 'user_registration') {
      return $this->view('emails.registration') // The view file for the email content
        ->with('data', $this->data) // Pass the data to the view
        ->subject($this->data['subject']); // Dynamic subject line
    } else {
      return null;
    }
  }
}