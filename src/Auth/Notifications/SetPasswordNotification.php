<?php

namespace AdminHelpers\Auth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetPasswordNotification extends Notification
{
    /**
     * @param  string  $token  password broker token
     * @param  string  $url  link of the client application setting the password
     */
    public function __construct(
        public $token,
        public $url,
    ) {}

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(_('Nastavenie hesla'))
            ->line(_('Váš účet bol vytvorený. Heslo si nastavíte cez odkaz nižšie.'))
            ->action(_('Nastaviť heslo'), $this->url)
            ->line(_('Platnosť odkazu je obmedzená. Nový odkaz získate cez obnovu hesla.'));
    }
}
