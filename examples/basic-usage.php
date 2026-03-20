<?php

declare(strict_types=1);

/**
 * Example: Using phpspec/prophecy-phpunit in a PHPUnit test.
 *
 * prophecy-phpunit integrates the Prophecy mock object library into PHPUnit
 * test cases. It provides ProphecyTrait, which manages mock creation,
 * assertion counting, and post-test verification automatically.
 *
 * Install:
 *   composer require --dev phpspec/prophecy-phpunit
 */

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

// --- Interface and class under test ---
interface Mailer
{
    public function send(string $to, string $subject, string $body): bool;
}

class NotificationService
{
    public function __construct(private Mailer $mailer) {}

    public function notifyUser(string $email, string $message): bool
    {
        return $this->mailer->send($email, 'Notification', $message);
    }
}

// --- Test using ProphecyTrait ---
class NotificationServiceTest extends TestCase
{
    use ProphecyTrait;

    public function test_sends_email_to_correct_address(): void
    {
        // Create a Prophecy mock for the Mailer interface
        $mailerProphecy = $this->prophesize(Mailer::class);

        // Set up expectations
        $mailerProphecy
            ->send('alice@example.com', 'Notification', 'Hello Alice!')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        // Inject the revealed mock (implements the interface)
        $service = new NotificationService($mailerProphecy->reveal());

        // Act
        $result = $service->notifyUser('alice@example.com', 'Hello Alice!');

        // Assert
        self::assertTrue($result);

        // ProphecyTrait::tearDownAfterClass() verifies all shouldBeCalled* predictions
        // automatically after the test — no manual verifyProphecyDoubles() needed.
    }

    public function test_returns_false_when_mailer_fails(): void
    {
        $mailerProphecy = $this->prophesize(Mailer::class);

        // Configure the mock to return false for any send() call
        $mailerProphecy->send('*', '*', '*')->willReturn(false);
        $mailerProphecy->send(\Prophecy\Argument::type('string'), '*', '*')->willReturn(false);

        // Using Argument::cetera() for wildcard matching
        $mailerProphecy
            ->send(\Prophecy\Argument::type('string'), \Prophecy\Argument::cetera())
            ->willReturn(false);

        $service = new NotificationService($mailerProphecy->reveal());
        $result  = $service->notifyUser('bob@example.com', 'Hi Bob!');

        self::assertFalse($result);
    }

    public function test_mailer_called_exact_times(): void
    {
        $mailerProphecy = $this->prophesize(Mailer::class);

        // Expect exactly 2 sends
        $mailerProphecy
            ->send(\Prophecy\Argument::type('string'), \Prophecy\Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalledTimes(2);

        $service = new NotificationService($mailerProphecy->reveal());
        $service->notifyUser('user1@example.com', 'Message 1');
        $service->notifyUser('user2@example.com', 'Message 2');
    }
}
