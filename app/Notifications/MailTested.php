<?php

namespace Pterodactyl\Notifications;

use Pterodactyl\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MailTested extends Notification
{
    /**
     * @var \Pterodactyl\Models\User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via()
    {
        return ['mail'];
    }

    public function toMail()
    {
        return (new MailMessage())
            ->subject('Pteodactyl Test Nachricht')
            ->greeting('Hallo ' . $this->user->name . '!')
            ->line('Dies ist ein Test des Pterodactyl-Mailsystems. Sie können loslegen!');
    }
}
