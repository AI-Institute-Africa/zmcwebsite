<?php

namespace App\Notifications;

use App\Models\DesignStudioTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DesignStudioTemplateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DesignStudioTemplate $template,
        public string $message,
        public string $action = 'view',
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'template_id'   => $this->template->id,
            'template_name' => $this->template->name,
            'template_type' => $this->template->type,
            'status'        => $this->template->status,
            'message'       => $this->message,
            'action'        => $this->action,
            'url'           => route('staff.design-studio.show', $this->template->id),
        ];
    }
}
