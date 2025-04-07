<?php

namespace App\Notifications;

use App\Models\EmergencyEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $patient;
    protected $event;

    public function __construct(User $patient, EmergencyEvent $event)
    {
        $this->patient = $patient;
        $this->event = $event;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ALERTĂ DE URGENȚĂ - ' . $this->patient->name)
            ->line('A fost primită o alertă de urgență de la pacientul dvs. ' . $this->patient->name)
            ->line('Tip eveniment: ' . $this->event->type)
            ->line('Data și ora: ' . $this->event->created_at->format('d.m.Y H:i:s'))
            ->line('Vă rugăm să contactați pacientul cât mai curând posibil.')
            ->action('Vezi detalii', url('/caregiver/emergencies/' . $this->event->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'event_id' => $this->event->id,
            'event_type' => $this->event->type,
            'event_time' => $this->event->created_at,
            'event_notes' => $this->event->notes,
        ];
    }
} 