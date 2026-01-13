<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Monitoring;

use App\Infrastructure\Monitoring\CustomMonitoringService;
use App\Infrastructure\Monitoring\MonitoringServiceInterface;
use PHPUnit\Framework\TestCase;

final class CustomMonitoringServiceTest extends TestCase
{
    private CustomMonitoringService $monitoringService;
    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testLogFile       = \sys_get_temp_dir() . '/test_api.log';
        $this->monitoringService = new CustomMonitoringService([
            'log_file' => $this->testLogFile,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test log file
        if (\file_exists($this->testLogFile)) {
            \unlink($this->testLogFile);
        }

        parent::tearDown();
    }

    public function testImplementsMonitoringServiceInterface(): void
    {
        $this->assertInstanceOf(
            MonitoringServiceInterface::class,
            $this->monitoringService
        );
    }

    public function testLogRequest(): void
    {
        $data = [
            'action'     => 'test.action',
            'user_id'    => 123,
            'ip_address' => '192.168.1.1',
        ];

        $this->monitoringService->logRequest($data);

        $logs = $this->monitoringService->getLogs();
        $this->assertCount(1, $logs);

        $logEntry = $logs[0];
        $this->assertEquals('INFO', $logEntry['level']);
        $this->assertEquals('HTTP Request', $logEntry['message']);
        $this->assertEquals($data, $logEntry['context']);
        $this->assertArrayHasKey('timestamp', $logEntry);

        // Verify file was written
        $this->assertFileExists($this->testLogFile);
        $fileContent = \file_get_contents($this->testLogFile);
        $this->assertStringContainsString('HTTP Request', $fileContent);
    }

    public function testLogError(): void
    {
        $exception = new \RuntimeException('Test error message');
        $context   = ['action' => 'test.action'];

        $this->monitoringService->logError($exception, $context);

        $logs = $this->monitoringService->getLogs();
        $this->assertCount(1, $logs);

        $logEntry = $logs[0];
        $this->assertEquals('ERROR', $logEntry['level']);
        $this->assertEquals('Test error message', $logEntry['message']);
        $this->assertEquals('RuntimeException', $logEntry['context']['exception']);
        $this->assertEquals($context['action'], $logEntry['context']['action']);
        $this->assertArrayHasKey('file', $logEntry['context']);
        $this->assertArrayHasKey('line', $logEntry['context']);
        $this->assertArrayHasKey('trace', $logEntry['context']);
    }

    public function testIncrementMetric(): void
    {
        $this->monitoringService->incrementMetric('test.counter', 5.0);
        $this->monitoringService->incrementMetric('test.counter', 3.0);

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(8.0, $metrics['counters']['test.counter']);
    }

    public function testIncrementMetricDefaultToOne(): void
    {
        $this->monitoringService->incrementMetric('test.counter');

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(1.0, $metrics['counters']['test.counter']);
    }

    public function testSetGauge(): void
    {
        $this->monitoringService->setGauge('test.gauge', 42.5);

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(42.5, $metrics['gauges']['test.gauge']);
    }

    public function testGetMetricsReturnsEmptyArraysInitially(): void
    {
        $metrics = $this->monitoringService->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('counters', $metrics);
        $this->assertArrayHasKey('gauges', $metrics);
        $this->assertEmpty($metrics['counters']);
        $this->assertEmpty($metrics['gauges']);
    }

    public function testGetLogsReturnsEmptyArrayInitially(): void
    {
        $logs = $this->monitoringService->getLogs();
        $this->assertIsArray($logs);
        $this->assertEmpty($logs);
    }

    public function testMultipleLogsAreStored(): void
    {
        $this->monitoringService->logRequest(['action' => 'test1']);
        $this->monitoringService->logRequest(['action' => 'test2']);

        $logs = $this->monitoringService->getLogs();
        $this->assertCount(2, $logs);
        $this->assertEquals('test1', $logs[0]['context']['action']);
        $this->assertEquals('test2', $logs[1]['context']['action']);
    }

    public function testLogDirectoryIsCreated(): void
    {
        $logFile = \sys_get_temp_dir() . '/new_dir/test_api.log';
        $service = new CustomMonitoringService(['log_file' => $logFile]);

        $service->logRequest(['test' => true]);

        $this->assertDirectoryExists(\dirname($logFile));
        $this->assertFileExists($logFile);

        // Cleanup
        \unlink($logFile);
        \rmdir(\dirname($logFile));
    }

    public function testDefaultLogFile(): void
    {
        $service = new CustomMonitoringService();
        $service->logRequest(['test' => true]);

        $logs = $service->getLogs();
        $this->assertCount(1, $logs);
    }
}
