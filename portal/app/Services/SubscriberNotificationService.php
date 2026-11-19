<?php

namespace App\Services;

use App\Mail\NewEventNotification;
use App\Mail\NewVacancyNotification;
use App\Models\Event;
use App\Models\Subscriber;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriberNotificationService
{
    /**
     * Send notification to all subscribers about a new vacancy
     */
    public function notifyNewVacancy(Vacancy $vacancy): int
    {
        $subscribers = Subscriber::all();
        $sentCount = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new NewVacancyNotification($vacancy));
                $sentCount++;
            } catch (\Exception $e) {
                Log::warning("Failed to send vacancy notification to {$subscriber->email}: " . $e->getMessage());
            }
        }

        Log::info("Sent vacancy notification '{$vacancy->title}' to {$sentCount} subscribers");
        return $sentCount;
    }

    /**
     * Send notification to all subscribers about a new event
     */
    public function notifyNewEvent(Event $event): int
    {
        $subscribers = Subscriber::all();
        $sentCount = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new NewEventNotification($event));
                $sentCount++;
            } catch (\Exception $e) {
                Log::warning("Failed to send event notification to {$subscriber->email}: " . $e->getMessage());
            }
        }

        Log::info("Sent event notification '{$event->title}' to {$sentCount} subscribers");
        return $sentCount;
    }

    /**
     * Queue notifications for better performance (use when subscriber count is high)
     */
    public function queueVacancyNotification(Vacancy $vacancy): void
    {
        $subscribers = Subscriber::all();

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->queue(new NewVacancyNotification($vacancy));
        }

        Log::info("Queued vacancy notification '{$vacancy->title}' for " . $subscribers->count() . " subscribers");
    }

    /**
     * Queue event notifications for better performance
     */
    public function queueEventNotification(Event $event): void
    {
        $subscribers = Subscriber::all();

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->queue(new NewEventNotification($event));
        }

        Log::info("Queued event notification '{$event->title}' for " . $subscribers->count() . " subscribers");
    }
}
