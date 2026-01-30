<?php

declare(strict_types=1);

namespace App\Seeder\Faker;

use Faker\Provider\Base;
use Faker\UniqueGenerator;
use App\Infrastructure\Seeder\SeederProviderInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SeedDataPoolFaker extends Base implements SeederProviderInterface
{
    protected static array $brands = [
        'Item Name 1', 'Item Name 2', 'Item Name 3', 'Item Name 4', 'Item Name 5', 'Item Name 6', 'Item Name 7', 'Item Name 8', 'Item Name 9', 'Item Name 10',
        'Item Name 11', 'Item Name 12', 'Item Name 13', 'Item Name 14', 'Item Name 15', 'Item Name 16', 'Item Name 17', 'Item Name 18', 'Item Name 19', 'Item Name 20', 'Item Name 21', 'Item Name 22', 'Item Name 23', 'Item Name 24', 'Item Name 25', 'Item Name 26', 'Item Name 27', 'Item Name 28', 'Item Name 29', 'Item Name 30', 'Item Name 31', 'Item Name 32', 'Item Name 33', 'Item Name 34', 'Item Name 35', 'Item Name 36', 'Item Name 37', 'Item Name 38', 'Item Name 39', 'Item Name 40', 'Item Name 41', 'Item Name 42', 'Item Name 43', 'Item Name 44', 'Item Name 45', 'Item Name 46', 'Item Name 47', 'Item Name 48', 'Item Name 49', 'Item Name 50',
    ];

    public function __construct(\Faker\Generator $generator)
    {
        parent::__construct($generator);
    }

    public function seedDataPoolRandom(): string
    {
        return self::randomElement(self::$brands);
    }
}