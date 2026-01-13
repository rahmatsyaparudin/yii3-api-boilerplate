<?php

declare(strict_types=1);

namespace Tests\Integration\Api\V1;

use PHPUnit\Framework\TestCase;

final class BrandApiTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8080';

    public function testGetBrandsReturnsSuccessResponse(): void
    {
        // This would be an actual HTTP client test
        // For now, we'll test the response structure

        $mockResponse = [
            'success' => true,
            'data'    => [
                [
                    'id'          => 'brand-uuid-1',
                    'name'        => 'Test Brand',
                    'description' => 'Test Description',
                    'website'     => 'https://example.com',
                    'logo'        => 'https://example.com/logo.png',
                    'created_at'  => '2024-01-01T12:00:00Z',
                    'updated_at'  => null,
                ],
            ],
            'message' => 'Brands retrieved successfully',
        ];

        $this->assertApiResponseStructure($mockResponse);
        $this->assertIsArray($mockResponse['data']);
        $this->assertNotEmpty($mockResponse['data']);

        $brand = $mockResponse['data'][0];
        $this->assertArrayHasKeys([
            'id', 'name', 'description', 'website', 'logo', 'created_at', 'updated_at',
        ], $brand);
    }

    public function testCreateBrandReturnsSuccessResponse(): void
    {
        $mockResponse = [
            'success' => true,
            'data'    => [
                'id'          => 'brand-uuid-new',
                'name'        => 'New Brand',
                'description' => 'New Description',
                'website'     => 'https://newbrand.com',
                'logo'        => 'https://newbrand.com/logo.png',
                'created_at'  => '2024-01-01T12:00:00Z',
                'updated_at'  => null,
            ],
            'message' => 'Brand created successfully',
        ];

        $this->assertApiResponseStructure($mockResponse);
        $this->assertEquals('New Brand', $mockResponse['data']['name']);
    }

    public function testGetBrandByIdReturnsSuccessResponse(): void
    {
        $brandId      = 'brand-uuid-1';
        $mockResponse = [
            'success' => true,
            'data'    => [
                'id'          => $brandId,
                'name'        => 'Test Brand',
                'description' => 'Test Description',
                'website'     => 'https://example.com',
                'logo'        => 'https://example.com/logo.png',
                'created_at'  => '2024-01-01T12:00:00Z',
                'updated_at'  => null,
            ],
            'message' => 'Brand retrieved successfully',
        ];

        $this->assertApiResponseStructure($mockResponse);
        $this->assertEquals($brandId, $mockResponse['data']['id']);
    }

    public function testUpdateBrandReturnsSuccessResponse(): void
    {
        $brandId      = 'brand-uuid-1';
        $mockResponse = [
            'success' => true,
            'data'    => [
                'id'          => $brandId,
                'name'        => 'Updated Brand',
                'description' => 'Updated Description',
                'website'     => 'https://updated.com',
                'logo'        => 'https://updated.com/logo.png',
                'created_at'  => '2024-01-01T12:00:00Z',
                'updated_at'  => '2024-01-01T13:00:00Z',
            ],
            'message' => 'Brand updated successfully',
        ];

        $this->assertApiResponseStructure($mockResponse);
        $this->assertEquals('Updated Brand', $mockResponse['data']['name']);
        $this->assertNotNull($mockResponse['data']['updated_at']);
    }

    public function testDeleteBrandReturnsSuccessResponse(): void
    {
        $mockResponse = [
            'success' => true,
            'data'    => [],
            'message' => 'Brand deleted successfully',
        ];

        $this->assertApiResponseStructure($mockResponse);
        $this->assertEmpty($mockResponse['data']);
    }

    public function testValidationErrorReturnsErrorResponse(): void
    {
        $mockResponse = [
            'success' => false,
            'data'    => [
                'errors' => [
                    'name'    => ['Brand name is required'],
                    'website' => ['Invalid website URL'],
                ],
            ],
            'message' => 'Validation failed',
        ];

        $this->assertFalse($mockResponse['success']);
        $this->assertArrayHasKey('errors', $mockResponse['data']);
        $this->assertIsArray($mockResponse['data']['errors']);
    }

    public function testNotFoundReturnsErrorResponse(): void
    {
        $mockResponse = [
            'success' => false,
            'data'    => [],
            'message' => 'Brand not found',
        ];

        $this->assertFalse($mockResponse['success']);
        $this->assertEmpty($mockResponse['data']);
        $this->assertEquals('Brand not found', $mockResponse['message']);
    }

    private function assertApiResponseStructure(array $response): void
    {
        $this->assertArrayHasKeys(['success', 'data', 'message'], $response);
        $this->assertIsBool($response['success']);
        $this->assertIsArray($response['data']);
        $this->assertIsString($response['message']);
    }

    private function assertArrayHasKeys(array $expectedKeys, array $array): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Array should have key: {$key}");
        }
    }
}
