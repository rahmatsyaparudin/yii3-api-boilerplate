<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\Core\ValueObject;

use App\Domain\Shared\Core\Enum\DocRole;
use App\Domain\Shared\Core\ValueObject\SyncIdentity;
use Codeception\Test\Unit;

final class SyncIdentityTest extends Unit
{
    public function testCreateGeneratesUuid(): void
    {
        $identity = SyncIdentity::create(sourceStoreId: 5);

        $this->assertTrue(SyncIdentity::isValidUuid($identity->getUuid()));
        $this->assertSame(5, $identity->getSourceStoreId());
        $this->assertSame(DocRole::LOCAL, $identity->getDocRole());
    }

    public function testGeneratedUuidsAreUnique(): void
    {
        $a = SyncIdentity::newUuid();
        $b = SyncIdentity::newUuid();

        $this->assertTrue(SyncIdentity::isValidUuid($a));
        $this->assertNotSame($a, $b);
    }

    public function testLocalIsEditableByUser(): void
    {
        $identity = SyncIdentity::local(sourceStoreId: 1);

        $this->assertTrue($identity->isLocal());
        $this->assertTrue($identity->isEditableByUser());
    }

    public function testCopyIsNotEditableByUser(): void
    {
        $identity = SyncIdentity::copy(uuid: SyncIdentity::newUuid(), sourceStoreId: 2);

        $this->assertTrue($identity->isCopy());
        $this->assertFalse($identity->isEditableByUser());
    }

    public function testIsOwnedBy(): void
    {
        $identity = SyncIdentity::local(sourceStoreId: 7);

        $this->assertTrue($identity->isOwnedBy(7));
        $this->assertFalse($identity->isOwnedBy(8));
        $this->assertFalse($identity->isOwnedBy(null));
    }

    public function testFromArray(): void
    {
        $uuid = SyncIdentity::newUuid();
        $identity = SyncIdentity::fromArray([
            'uuid'            => $uuid,
            'source_store_id' => 3,
            'doc_role'        => 'INTERSTORE',
        ]);

        $this->assertSame($uuid, $identity->getUuid());
        $this->assertSame(3, $identity->getSourceStoreId());
        $this->assertSame(DocRole::INTERSTORE, $identity->getDocRole());
        $this->assertTrue($identity->isInterstore());
    }

    public function testFromArrayDefaults(): void
    {
        $identity = SyncIdentity::fromArray([]);

        $this->assertTrue(SyncIdentity::isValidUuid($identity->getUuid()));
        $this->assertNull($identity->getSourceStoreId());
        $this->assertSame(DocRole::LOCAL, $identity->getDocRole());
    }

    public function testFromEntity(): void
    {
        $uuid = SyncIdentity::newUuid();
        $entity = new class ($uuid) {
            public function __construct(private string $uuid) {}
            public function getUuid(): string { return $this->uuid; }
            public function getSourceStoreId(): ?int { return 9; }
            public function getDocRoleValue(): string { return 'COPY'; }
        };

        $identity = SyncIdentity::fromEntity($entity);

        $this->assertSame($uuid, $identity->getUuid());
        $this->assertSame(9, $identity->getSourceStoreId());
        $this->assertSame(DocRole::COPY, $identity->getDocRole());
    }

    public function testInvalidDocRoleThrows(): void
    {
        $this->expectException(\Throwable::class);

        SyncIdentity::fromArray(['doc_role' => 'BOGUS']);
    }

    public function testToDbArray(): void
    {
        $uuid = SyncIdentity::newUuid();
        $identity = SyncIdentity::create(uuid: $uuid, sourceStoreId: 4);

        $this->assertSame(
            [
                'uuid'            => $uuid,
                'source_store_id' => 4,
                'doc_role'        => 'LOCAL',
            ],
            $identity->toDbArray(),
        );
    }
}
