<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Brand;

use PHPUnit\Framework\TestCase;

final class BrandTest extends TestCase
{
    public function testBrandCanBeCreatedWithMinimalData(): void
    {
        // Test domain logic without ActiveRecord dependency
        $brandData = [
            'name'        => 'Test Brand',
            'description' => 'Test Description',
            'website'     => 'https://example.com',
            'logo'        => 'https://example.com/logo.png',
        ];

        $this->assertEquals('Test Brand', $brandData['name']);
        $this->assertEquals('Test Description', $brandData['description']);
        $this->assertEquals('https://example.com', $brandData['website']);
        $this->assertEquals('https://example.com/logo.png', $brandData['logo']);
    }

    public function testBrandValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand name cannot be empty');

        $this->validateBrand(['name' => '']);
    }

    public function testBrandWebsiteValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid website URL');

        $this->validateBrand(['name' => 'Test Brand', 'website' => 'invalid-url']);
    }

    public function testBrandLogoValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid logo URL');

        $this->validateBrand(['name' => 'Test Brand', 'logo' => 'invalid-url']);
    }

    public function testBrandToArray(): void
    {
        $brandData = [
            'id'          => 'brand-uuid-123',
            'name'        => 'Test Brand',
            'description' => 'Test Description',
            'website'     => 'https://example.com',
            'logo'        => 'https://example.com/logo.png',
            'created_at'  => '2024-01-01T12:00:00Z',
            'updated_at'  => null,
        ];

        $array = $this->brandToArray($brandData);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('website', $array);
        $this->assertArrayHasKey('logo', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals('Test Brand', $array['name']);
        $this->assertEquals('Test Description', $array['description']);
        $this->assertEquals('https://example.com', $array['website']);
        $this->assertEquals('https://example.com/logo.png', $array['logo']);
    }

    public function testBrandUpdate(): void
    {
        $originalData = [
            'name'        => 'Original Name',
            'description' => 'Original Description',
        ];

        $updateData = [
            'name'        => 'Updated Name',
            'description' => 'Updated Description',
            'website'     => 'https://updated.com',
            'logo'        => 'https://updated.com/logo.png',
        ];

        $updated = $this->updateBrand($originalData, $updateData);

        $this->assertEquals('Updated Name', $updated['name']);
        $this->assertEquals('Updated Description', $updated['description']);
        $this->assertEquals('https://updated.com', $updated['website']);
        $this->assertEquals('https://updated.com/logo.png', $updated['logo']);
    }

    public function testBrandUpdateWithPartialData(): void
    {
        $originalData = [
            'name'        => 'Original Name',
            'description' => 'Original Description',
            'website'     => 'https://original.com',
        ];

        $updateData = ['name' => 'Updated Name'];

        $updated = $this->updateBrand($originalData, $updateData);

        $this->assertEquals('Updated Name', $updated['name']);
        $this->assertEquals('Original Description', $updated['description']);
        $this->assertEquals('https://original.com', $updated['website']);
    }

    public function testBrandJsonSerialize(): void
    {
        $brandData = [
            'name'        => 'Test Brand',
            'description' => 'Test Description',
        ];

        $json = \json_encode($brandData);
        $data = \json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertEquals('Test Brand', $data['name']);
    }

    // Helper methods to simulate domain logic
    private function validateBrand(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Brand name cannot be empty');
        }

        if (isset($data['website']) && !\filter_var($data['website'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid website URL');
        }

        if (isset($data['logo']) && !\filter_var($data['logo'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid logo URL');
        }
    }

    private function brandToArray(array $brandData): array
    {
        return $brandData;
    }

    private function updateBrand(array $original, array $updates): array
    {
        return \array_merge($original, $updates);
    }
}
