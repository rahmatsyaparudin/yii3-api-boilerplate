<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Shared;

use App\Api\Shared\ResponseFactory;
use Codeception\Test\Unit;
use HttpSoft\Message\ResponseFactory as PsrResponseFactory;
use HttpSoft\Message\StreamFactory;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;

final class ResponseFactoryTest extends Unit
{
    public function testSuccess(): void
    {
        $response = $this
            ->createResponseFactory()
            ->success(['name' => 'test']);

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertSame(
            [
                'code'    => 200,
                'success' => true,
                'message' => 'Success',
                'data'    => ['name' => 'test'],
            ],
            $response->getData(),
        );
    }

    public function testFail(): void
    {
        $response = $this
            ->createResponseFactory()
            ->fail(translate: 'error text');

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertSame(
            [
                'code'    => 400,
                'success' => false,
                'message' => 'error text',
                'errors'  => [],
            ],
            $response->getData(),
        );
    }

    public function testNotFound(): void
    {
        $response = $this
            ->createResponseFactory()
            ->notFound();

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertSame(
            [
                'code'    => 404,
                'success' => false,
                'message' => 'Not found.',
                'errors'  => [],
            ],
            $response->getData(),
        );
    }

    public function testFailValidation(): void
    {
        $result = (new Result())
            ->addError('error1', valuePath: ['name'])
            ->addError('error2', valuePath: ['name'])
            ->addError('error3', valuePath: ['age']);
        $response = $this
            ->createResponseFactory()
            ->failValidation($result);

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertSame(
            [
                'code'    => 422,
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'name' => ['error1', 'error2'],
                    'age'  => ['error3'],
                ],
            ],
            $response->getData(),
        );
    }

    private function createResponseFactory(): ResponseFactory
    {
        return new ResponseFactory(
            new DataResponseFactory(
                new PsrResponseFactory(),
                new StreamFactory(),
            ),
            $this->createTranslator(),
        );
    }

    private function createTranslator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            public function addCategorySources(CategorySource ...$categories): static
            {
                return $this;
            }

            public function setLocale(string $locale): static
            {
                return $this;
            }

            public function getLocale(): string
            {
                return 'en';
            }

            public function translate(
                string|\Stringable $id,
                array $parameters = [],
                ?string $category = null,
                ?string $locale = null,
            ): string {
                return match ($id) {
                    'http.not_found'    => 'Not found.',
                    'validation.failed' => 'Validation failed.',
                    default             => (string) $id,
                };
            }

            public function withDefaultCategory(string $category): static
            {
                return $this;
            }

            public function withLocale(string $locale): static
            {
                return $this;
            }
        };
    }
}
