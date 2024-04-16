<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/messenger-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Messenger\Test\Tests\Transport;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Zenstruck\Messenger\Test\Transport\TestTransport;
use Zenstruck\Messenger\Test\Transport\TestTransportFactory;

/** @covers \Zenstruck\Messenger\Test\Transport\TestTransportFactory */
final class TestTransportFactoryTest extends TestCase
{
    private Stub&MessageBusInterface $bus;
    private Stub&EventDispatcherInterface $dispatcher;
    private Stub&ClockInterface $clock;
    private Stub&SerializerInterface $serializer;

    protected function setUp(): void
    {
        // Reset statics
        TestTransport::resetAll();

        $this->bus = $this->createStub(MessageBusInterface::class);
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        $this->clock = $this->createStub(ClockInterface::class);
        $this->serializer = $this->createStub(SerializerInterface::class);
    }

    /**
     * @dataProvider provideCreateTransportCases
     *
     * @param array<string, bool> $options
     * @param array<string, bool> $expectedOptions
     *
     * @covers \Zenstruck\Messenger\Test\Transport\TestTransport::isIntercepting()
     * @covers \Zenstruck\Messenger\Test\Transport\TestTransport::isCatchingExceptions()
     * @covers \Zenstruck\Messenger\Test\Transport\TestTransport::shouldTestSerialization()
     * @covers \Zenstruck\Messenger\Test\Transport\TestTransport::isRetriesDisabled()
     * @covers \Zenstruck\Messenger\Test\Transport\TestTransport::supportsDelayStamp()
     *
     * @test
     */
    public function create_transport(string $dsn, array $options, array $expectedOptions): void
    {
        $factory = new TestTransportFactory(
            $this->bus,
            $this->dispatcher,
            $this->clock
        );

        $transport = $factory->createTransport($dsn, [
            'transport_name' => 'some-transport-name',
        ] + $options, $this->serializer);
        self::assertInstanceOf(TestTransport::class, $transport);
        self::assertEquals($expectedOptions, [
            'intercept' => $transport->isIntercepting(),
            'catch_exceptions' => $transport->isCatchingExceptions(),
            'test_serialization' => $transport->shouldTestSerialization(),
            'disable_retries' => $transport->isRetriesDisabled(),
            'support_delay_stamp' => $transport->supportsDelayStamp(),
        ]);
    }

    /**
     * @return iterable<array{string, array<string, bool>, array<string, bool>}>
     */
    public static function provideCreateTransportCases(): iterable
    {
        $defaults = [
            'intercept' => true,
            'catch_exceptions' => true,
            'test_serialization' => true,
            'disable_retries' => true,
            'support_delay_stamp' => false,
        ];

        yield 'pass options by dsn only' => ['test://?intercept=false&support_delay_stamp=true', [], [
            'intercept' => false,
            'support_delay_stamp' => true,
        ] + $defaults];

        yield 'pass options by options only' => ['test://', [
            'intercept' => false,
            'support_delay_stamp' => true,
        ], [
            'intercept' => false,
            'support_delay_stamp' => true,
        ] + $defaults];

        yield 'pass options by dns and options only' => ['test://?catch_exceptions=false&support_delay_stamp=false', [
            'catch_exceptions' => true,
            'support_delay_stamp' => true,
        ], [
            'catch_exceptions' => true,
            'support_delay_stamp' => true,
        ] + $defaults];
    }

    /**
     * @testWith ["test://", true]
     *           ["another-test://", false]
     *
     * @test
     */
    public function support(string $dsn, bool $expectedSupport): void
    {
        $factory = new TestTransportFactory(
            $this->bus,
            $this->dispatcher,
            $this->clock
        );
        self::assertSame($expectedSupport, $factory->supports($dsn, []));
    }
}
