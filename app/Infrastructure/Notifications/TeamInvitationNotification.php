<?php

namespace App\Infrastructure\Notifications;

use App\Infrastructure\Persistence\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TeamInvitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = url('/invitations/' . $this->invitation->token . '/accept');

        return (new MailMessage)
            ->subject('You\'ve been invited to join ' . $this->invitation->agency->name . ' on PropOS')
            ->greeting('Hello!')
            ->line('You have been invited to join **' . $this->invitation->agency->name . '** on PropOS as a **' . ucfirst($this->invitation->role) . '**.')
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation expires in 7 days.')
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
