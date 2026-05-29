<?php

namespace App\Infrastructure\Notifications;

use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contact $contact) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Contact: ' . $this->contact->first_name . ' ' . $this->contact->last_name)
            ->greeting('Hi ' . $notifiable->first_name . ',')
            ->line('A new contact has been added to your CRM.')
            ->line('**Name:** ' . $this->contact->first_name . ' ' . $this->contact->last_name)
            ->line('**Type:** ' . ucfirst($this->contact->type))
            ->when($this->contact->email, fn($m) => $m->line('**Email:** ' . $this->contact->email))
            ->when($this->contact->phone, fn($m) => $m->line('**Phone:** ' . $this->contact->phone))
            ->action('View Contact', url('/contacts/' . $this->contact->id))
            ->line('Log activities and track this contact in your PropOS CRM.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contact_created',
            'title' => 'New contact: ' . $this->contact->first_name . ' ' . $this->contact->last_name,
            'body' => ucfirst($this->contact->type) . ' added to CRM.',
            'action_url' => '/contacts/' . $this->contact->id,
            'severity' => 'info',
        ];
    }
}
