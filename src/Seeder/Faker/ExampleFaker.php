<?php

declare(strict_types=1);

namespace App\Seeder\Faker;

use Faker\Provider\Base;
use Faker\UniqueGenerator;
use App\Infrastructure\Seeder\SeederProviderInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ExampleFaker extends Base implements SeederProviderInterface
{
    protected static array $brands = [
        'ASUS ROG', 'MSI Gaming', 'Gigabyte AORUS', 'NVIDIA', 
        'AMD Ryzen', 'Intel Core', 'Corsair', 'EVGA', 'Cooler Master'
    ];

    public function __construct(\Faker\Generator $generator)
    {
        parent::__construct($generator);
    }

    public function exampleRandom(): string
    {
        return self::randomElement(self::$brands);
    }
}