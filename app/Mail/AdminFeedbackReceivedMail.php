<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminFeedbackReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $store;

    public function __construct(Feedback $feedback, Store $store)
    {
        $this->feedback = $feedback;
        $this->store = $store;
    }

    public function build()
    {
        $mail = $this->subject('New Feedback Received')
            ->view('emails.admin-feedback-received');

        if (!empty($this->feedback->customer_email)) {
            $mail->replyTo($this->feedback->customer_email, $this->feedback->customer_name);
        }

        return $mail;
    }
}