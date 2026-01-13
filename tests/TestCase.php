<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

// Base test case class for Yii3 applications
abstract class Yii3TestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup test environment
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_DSN']  = 'sqlite::memory:';

        // Clear any existing state
        if (\function_exists('gc_collect_cycles')) {
            \gc_collect_cycles();
        }
    }

    protected function tearDown(): void
    {
        // Cleanup test environment
        parent::tearDown();

        // Clear memory
        if (\function_exists('gc_collect_cycles')) {
            \gc_collect_cycles();
        }
    }

    /**
     * Create a mock service with DI container support.
     */
    protected function createMockService(string $className): object
    {
        return $this->createMock($className);
    }

    /**
     * Assert array has expected keys.
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Array should have key: {$key}");
        }
    }

    /**
     * Assert response structure for API responses.
     */
    protected function assertApiResponseStructure(array $response): void
    {
        $this->assertArrayHasKeys(['success', 'data', 'message'], $response);
        $this->assertIsBool($response['success']);
        $this->assertIsArray($response['data']);
        $this->assertIsString($response['message']);
    }
}
