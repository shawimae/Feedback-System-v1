<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerFeedbackReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $store;
    public $customer;
    public $pointsEarned;
    public $replyMessage;

    public function __construct(
        Feedback $feedback,
        Store $store,
        ?Customer $customer,
        int $pointsEarned,
        string $replyMessage
    ) {
        $this->feedback = $feedback;
        $this->store = $store;
        $this->customer = $customer;
        $this->pointsEarned = $pointsEarned;
        $this->replyMessage = $replyMessage;
    }

    public function build()
    {
        return $this->subject('Response to Your Feedback - ' . ($this->store->name ?? 'Our Store'))
            ->view('emails.customer-feedback-reply');
    }
}
