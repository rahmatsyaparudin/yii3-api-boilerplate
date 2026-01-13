<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Middleware;

use App\Shared\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new RateLimitMiddleware(
            maxRequests: 10,
            windowSize: 60
        );

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testProcessReturnsResponseFromHandler(): void
    {
        // Create a proper ResponseInterface mock
        $response = $this->createMock(ResponseInterface::class);

        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '192.168.1.1']);

        $this->handler
            ->method('handle')
            ->with($this->request)
            ->willReturn($response);

        // Mock the withHeader method to return the same response
        $response->expects($this->exactly(3))
            ->method('withHeader')
            ->willReturnSelf();

        $result = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($response, $result);
    }

    public function testConstructorAcceptsParameters(): void
    {
        $middleware = new RateLimitMiddleware(
            maxRequests: 100,
            windowSize: 300
        );

        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    public function testProcessWithDifferentIpAddresses(): void
    {
        // Create a proper ResponseInterface mock
        $response = $this->createMock(ResponseInterface::class);

        // First request from IP 1
        $request1 = $this->createMock(ServerRequestInterface::class);
        $request1->method('getServerParams')->willReturn(['REMOTE_ADDR' => '192.168.1.1']);

        // Second request from IP 2
        $request2 = $this->createMock(ServerRequestInterface::class);
        $request2->method('getServerParams')->willReturn(['REMOTE_ADDR' => '192.168.1.2']);

        $this->handler
            ->method('handle')
            ->willReturn($response);

        // Mock the withHeader method to return the same response
        $response->expects($this->exactly(6))
            ->method('withHeader')
            ->willReturnSelf();

        $response1 = $this->middleware->process($request1, $this->handler);
        $response2 = $this->middleware->process($request2, $this->handler);

        $this->assertSame($response, $response1);
        $this->assertSame($response, $response2);
    }
}
