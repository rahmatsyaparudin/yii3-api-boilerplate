<?php

declare(strict_types=1);

namespace App\Application\Product;

// Application Layer
use App\Application\Product\Command\CreateProductCommand;
use App\Application\Product\Command\UpdateProductCommand;
use App\Application\Product\Dto\ProductResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Service\ProductDomainService;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Security\AuthorizerInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Product Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class ProductApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private ProductRepositoryInterface $repository,
        private ProductDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return Product::RESOURCE;
    }

    private function getEntityById(int $id): Product
    {
        $product = $this->repository->findById($id);
        
        if ($product === null) {
            throw new NotFoundException(
                translate: new Message(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $product;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list($criteria);
    }

    public function view(int $id): ProductResponse
    {
        $product = $this->getEntityById($id);
        return ProductResponse::fromEntity($product);
    }

    public function get(int $id): ProductResponse
    {
        return $this->view($id);
    }

    public function create(CreateProductCommand $command): ProductResponse
    {
        $this->domainService->validateUniqueValue(
            value: $command->name,
            field: 'name',
            resource: Product::RESOURCE,
            repository: $this->repository,
            excludeId: null
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->withApproved()
            ->build();

        $product = Product::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfo,
            syncMdb: $command->syncMdb !== null ? ($command->syncMdb ? 1 : 0) : null
        );

        return ProductResponse::fromEntity(product: $this->repository->insert($product));
    }

    public function update(int $id, UpdateProductCommand $command): ProductResponse
    {
        $product = $this->getEntityById($id);

        $product->verifyLockVersion($command->lockVersion);

        $newStatus = Status::tryFrom($command->status);

        $hasFieldChanges = $product->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $product->validateStateTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        if (isset($command->name)) {
            $this->domainService->validateUniqueValue(
                field: 'name',
                value: $command->name,
                resource: $this->getResource(),
                repository: $this->repository,
                excludeId: $id
            );

            $product->changeName($command->name);
        }

        if ($newStatus !== null) {
            $product->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $product->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $product->updateDetailInfo(detailInfo: $detailInfo);

        return ProductResponse::fromEntity($this->repository->update($product));
    }

    public function delete(int $id): ProductResponse
    {
        $product = $this->getEntityById($id);

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: 'product.delete',
            resource: $this->getResource(),
            id: $id
        );
        
        $this->domainService->validateCanBeDeleted(
            entity: $product,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $product->getDetailInfo(),
                payload: [],
            )
            ->build();

        $product->updateDetailInfo(detailInfo: $detailInfo);

        return ProductResponse::fromEntity($this->repository->delete($product));
    }

    public function restore(int $id): ProductResponse
    {
        $product = $this->repository->restore($id);
        
        if ($product === null) {
            throw new NotFoundException(
                translate: new Message(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $newStatus = Status::draft();

        $product->validateStateTransition(
            hasFieldChanges: false,
            newStatus: $newStatus
        );

        if ($newStatus !== null) {
            $product->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $product->getDetailInfo(),
                payload: [],
            )
            ->build();

        $product->updateDetailInfo(detailInfo: $detailInfo);

        $restoredProduct = $this->repository->restore($product->getId());
        
        return ProductResponse::fromEntity($restoredProduct);
    }
}
