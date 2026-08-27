<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\Store;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerRewardMail extends Mailable
{
    use Queueable, SerializesModels;

    public $feedback;
    public $store;
    public $customer;
    public $points;

    public function __construct(Feedback $feedback, Store $store, Customer $customer, $points)
    {
        $this->feedback = $feedback;
        $this->store = $store;
        $this->customer = $customer;
        $this->points = $points;
    }

    public function build()
    {
        return $this->subject('Your Google Review Reward Has Been Approved')
            ->view('emails.customer-reward');
    }
}
