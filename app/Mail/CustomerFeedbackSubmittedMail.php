<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerFeedbackSubmittedMail extends Mailable
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
        return $this->subject('Feedback Submission Confirmation - ' . ($this->store->name ?? 'Our Store'))
            ->view('emails.customer-feedback-submitted');
    }
}
