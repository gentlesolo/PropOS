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
        $agency    = $this->invitation->agency;
        $acceptUrl = url('/invitations/' . $this->invitation->token . '/accept');

        return (new MailMessage)
            ->subject('You\'ve been invited to join ' . $agency->name)
            ->view('notifications.team-invitation', [
                'invitation'   => $this->invitation,
                'agency'       => $agency,
                'acceptUrl'    => $acceptUrl,
                'primaryColor' => $agency->primary_color ?? '#10B981',
            ]);
    }
}
