<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\VisitSchedule;

class VisitScheduleReminderNotification extends Notification
{
    public function __construct(
        public VisitSchedule $schedule,
        public string $type // 'H-1', 'H0', 'H-1h'
    ) {}

    public function via($notifiable): array
    {
        return ['mail']; // fokus email dulu
    }

    public function toMail($notifiable): MailMessage
    {
        $s = $this->schedule;

        $judul = match ($this->type) {
            'H-1' => 'Reminder: Besok ada visit',
            'H0'  => 'Reminder: Hari ini ada visit',
            'H-1h'=> 'Reminder: 1 jam lagi ada visit',
            default => 'Reminder Visit',
        };

        return (new MailMessage)
            ->subject($judul)
            ->line($s->title ?: 'Visit')
            ->line('Customer: '.($s->customer->name ?? '—'))
            ->line('Waktu   : '.optional($s->scheduled_at)->format('d M Y, H:i'))
            ->line('Lokasi  : '.($s->location ?: '—'));
    }
}
