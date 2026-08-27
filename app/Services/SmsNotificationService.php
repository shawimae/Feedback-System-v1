<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Feedback;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class SmsNotificationService
{
    public function sendRewardNotification(?Customer $customer, Feedback $feedback, string $phone, string $message): NotificationLog
    {
        $driver = config('services.sms.driver', 'log');

        $log = NotificationLog::create([
            'customer_id' => $customer?->customer_id,
            'feedback_id' => $feedback->feedback_id,
            'channel' => 'sms',
            'recipient' => $phone,
            'subject' => 'Reward Notification',
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            if ($driver === 'log') {
                $log->update(['status' => 'sent']);
                return $log;
            }

            if ($driver === 'semaphore') {
                $apiKey = config('services.sms.semaphore.api_key');
                $sender = config('services.sms.semaphore.sender_name');

                if (!$apiKey) {
                    throw new \RuntimeException('SMS API key is not configured.');
                }

                Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
                    'apikey' => $apiKey,
                    'number' => $phone,
                    'message' => $message,
                    'sendername' => $sender,
                ])->throw();

                $log->update(['status' => 'sent']);
                return $log;
            }

            throw new \RuntimeException('Unsupported SMS driver: ' . $driver);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $log;
        }
    }
}
