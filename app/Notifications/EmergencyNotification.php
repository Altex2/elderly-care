<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Urgență: ' . $this->user->name . ' are nevoie de ajutor!')
            ->line($this->user->name . ' a solicitat ajutor de urgență.')
            ->line('Vă rugăm să luați legătura imediat.')
            ->action('Vezi detalii', url('/caregiver/dashboard'));
    }

    public function toArray($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'message' => 'Solicitare de ajutor de urgență'
        ];
    }
} 