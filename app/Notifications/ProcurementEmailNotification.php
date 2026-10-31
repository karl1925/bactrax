<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ProcurementEmailNotification extends Notification
{
    use Queueable;

    /** @param list<string> $lines
     *  @param list<array{label: string, value: string}> $details
     */
    public function __construct(
        private readonly string $subject,
        private readonly array $lines,
        private readonly string $url,
        private readonly string $actionLabel = 'Open purchase request',
        private readonly string $type = 'procurement',
        private readonly array $details = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return $this->notificationData();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->notificationData());
    }

    /** @return array<string, mixed> */
    private function notificationData(): array
    {
        return [
            'title' => $this->subject,
            'message' => implode(' ', $this->lines),
            'url' => $this->url,
            'type' => $this->type,
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = [
            'recipientName' => ($notifiable->name ?? null) ?: 'there',
            'subject' => $this->subject,
            'lines' => $this->lines,
            'url' => $this->url,
            'actionLabel' => $this->actionLabel,
            'appUrl' => config('app.url'),
            'details' => $this->details,
        ];

        return (new MailMessage())
            ->subject($this->subject)
            ->view('emails.notifications.procurement', $data)
            ->text('emails.notifications.procurement-text', $data);
    }
}
