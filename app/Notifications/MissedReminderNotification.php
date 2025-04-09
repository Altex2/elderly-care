<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissedReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $patient;
    protected $reminder;

    /**
     * Create a new notification instance.
     */
    public function __construct($reminder, $patient)
    {
        $this->patient = $patient;
        $this->reminder = $reminder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Base channels that are always enabled
        $channels = ['database', 'broadcast'];
        
        // Only include email channel if explicitly enabled in .env
        if (env('NOTIFICATIONS_EMAIL_ENABLED', false)) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Pacientul nu a completat un memento la timp')
                    ->line("Pacientul {$this->patient->name} nu a completat memento-ul \"{$this->reminder->title}\" la timp.")
                    ->line("Memento-ul a fost programat pentru {$this->reminder->next_occurrence->format('Y-m-d H:i:s')}.")
                    ->action('Vezi detalii', url('/caregiver/dashboard'))
                    ->line('Mulțumim pentru utilizarea aplicației noastre!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'reminder_title' => $this->reminder->title,
            'scheduled_time' => $this->reminder->next_occurrence ? $this->reminder->next_occurrence->format('Y-m-d H:i:s') : 'N/A',
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'message' => "Pacientul {$this->patient->name} nu a completat memento-ul \"{$this->reminder->title}\" la timp."
        ];
    }
}
