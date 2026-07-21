<?php

namespace AdminHelpers\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use AdminHelpers\Notifications\Utilities\NotificationManager;

/**
 * Delivers a single instant notification (chat message, like, ...) right after it is
 * created, off the web request. The scheduled command handles everything else.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $notificationId)
    {
    }

    public function handle(): void
    {
        (new NotificationManager(null))->process($this->notificationId);
    }
}
