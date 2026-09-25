<?php

namespace AdminHelpers\Tests\Feature;

use AdminHelpers\Sms\SmartSms;
use AdminHelpers\Sms\SmartSmsChannel;
use AdminHelpers\Tests\TestCase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\Test;

class SmartSmsChannelTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        // Messages are only logged into a test handler, nothing is sent to smartsms.sk
        $app['config']->set('logging.channels.sms', [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
        ]);

        $app['config']->set('smartsms', [
            'log_channel' => 'sms',
            'test' => true,
            'from' => 'CrudAdmin',
            'username' => 'user',
            'password' => md5('secret'),
        ]);
    }

    #[Test]
    public function helpers_register_the_sms_notification_channel()
    {
        $this->assertInstanceOf(SmartSmsChannel::class, $this->app->make(ChannelManager::class)->driver('sms'));
    }

    #[Test]
    public function to_sms_is_sent_to_the_route_of_the_notifiable()
    {
        (new SmsNotifiable('+421900000001', '+421900000002'))->notify(new ToSmsNotification('Your code is 1234'));

        $this->assertSame(['TESTING [+421900000001:]: Your code is 1234'], $this->loggedMessages());
    }

    #[Test]
    public function get_message_is_used_without_to_sms_and_phone_without_a_route()
    {
        (new SmsNotifiable(null, '+421900000002'))->notify(new GetMessageNotification('Code 5678'));

        $this->assertSame(['TESTING [+421900000002:]: Code 5678'], $this->loggedMessages());
    }

    #[Test]
    public function a_notifiable_without_routes_is_sent_to_its_phone()
    {
        $notifiable = new class
        {
            public $phone = '+421900000003';
        };

        $this->app->make(ChannelManager::class)->driver('sms')->send($notifiable, new ToSmsNotification('Plain'));

        $this->assertSame(['TESTING [+421900000003:]: Plain'], $this->loggedMessages());
    }

    #[Test]
    public function smart_sms_only_logs_the_message_while_testing()
    {
        $this->assertTrue((new SmartSms)->sendSMS('+421900000004', "Kód: čšž\nnový riadok", 'Sender'));

        // Accents are removed and new lines are left out of the log
        $this->assertSame(['TESTING [+421900000004:Sender]: Kod: csznovy riadok'], $this->loggedMessages());
    }

    private function loggedMessages(): array
    {
        $handler = Log::channel('sms')->getLogger()->getHandlers()[0];

        return array_map(fn ($record) => $record->message, $handler->getRecords());
    }
}

class SmsNotifiable
{
    use Notifiable;

    public function __construct(
        public ?string $route,
        public ?string $phone,
    ) {}

    public function routeNotificationForSms($notification)
    {
        return $this->route;
    }

    public function getKey()
    {
        return 1;
    }
}

class ToSmsNotification extends Notification
{
    public function __construct(private string $message) {}

    public function via($notifiable)
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return $this->message;
    }
}

class GetMessageNotification extends Notification
{
    public function __construct(private string $message) {}

    public function via($notifiable)
    {
        return ['sms'];
    }

    public function getMessage($notifiable)
    {
        return $this->message;
    }
}
