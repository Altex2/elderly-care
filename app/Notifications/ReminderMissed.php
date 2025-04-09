<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderMissed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reminder;
    protected $patient;

    public function __construct($reminder, $patient)
    {
        $this->reminder = $reminder;
        $this->patient = $patient;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'reminder' => [
                'id' => $this->reminder->id,
                'title' => $this->reminder->title,
                'next_occurrence' => $this->reminder->next_occurrence,
            ],
            'patient_id' => $this->patient->id,
            'message' => "Pacientul {$this->patient->name} nu a completat memento-ul '{$this->reminder->title}' la timp."
        ];
    }
} 