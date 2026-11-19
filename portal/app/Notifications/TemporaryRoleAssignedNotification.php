<?php

namespace App\Notifications;

use App\Models\TemporaryRoleAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryRoleAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public TemporaryRoleAssignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $assignment = $this->assignment;
        $roleName = ucwords(str_replace('_', ' ', $assignment->temporary_role));
        $regionName = $assignment->region?->name ?? 'All regions';

        $mail = (new MailMessage)
            ->subject('Temporary role assigned - Zimbabwe Media Commission')
            ->greeting('Hello ' . ($notifiable->name ?: 'colleague') . ',')
            ->line('A temporary staff role has been assigned to your account.')
            ->line('Role: ' . $roleName)
            ->line('Starts: ' . $assignment->starts_at->format('d M Y'))
            ->line('Ends: ' . $assignment->ends_at->format('d M Y'))
            ->line('Coverage: ' . $regionName);

        if (filled($assignment->reason)) {
            $mail->line('Reason: ' . $assignment->reason);
        }

        return $mail
            ->action('Open Staff Portal', route('staff.login'))
            ->line('The temporary access is limited to the dates shown above. If you were not expecting this assignment, contact the IT Administration team.')
            ->salutation('Regards, Zimbabwe Media Commission IT Team');
    }
}
