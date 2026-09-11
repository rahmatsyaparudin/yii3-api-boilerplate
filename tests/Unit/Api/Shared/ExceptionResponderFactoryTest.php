<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Shared;

use App\Api\Shared\ExceptionResponderFactory;
use App\Api\Shared\ResponseFactory;
use Codeception\Test\Unit;
use HttpSoft\Message\ResponseFactory as PsrResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactory;
use Yiisoft\Di\Container;
use Yiisoft\ErrorHandler\Exception\UserException;
use Yiisoft\ErrorHandler\Middleware\ExceptionResponder;
use Yiisoft\Injector\Injector;
use Yiisoft\Input\Http\InputValidationException;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;

final class ExceptionResponderFactoryTest extends Unit
{
    public function testInputValidationException(): void
    {
        $request = new ServerRequest();
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new InputValidationException((new Result())->addError('error1', valuePath: ['name']));
            }
        };

        $response = $this->createExceptionResponder()->process($request, $handler);

        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertSame(
            [
                'code'    => 422,
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'name' => ['error1'],
                ],
            ],
            $response->getData(),
        );
    }

    public function testUserException(): void
    {
        $request = new ServerRequest();
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new UserException('Hello, Exception!');
            }
        };

        $response = $this->createExceptionResponder()->process($request, $handler);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            [
                'code'    => 400,
                'success' => false,
                'message' => 'Hello, Exception!',
                'errors'  => [],
            ],
            \json_decode((string) $response->getBody(), true),
        );
    }

    public function testOtherThrowable(): void
    {
        $request = new ServerRequest();
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('Hello, Exception!');
            }
        };

        $response = $this->createExceptionResponder()->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            [
                'code'    => 500,
                'success' => false,
                'message' => 'Hello, Exception!',
                'errors'  => [],
            ],
            \json_decode((string) $response->getBody(), true),
        );
    }

    private function createExceptionResponder(): ExceptionResponder
    {
        return (new ExceptionResponderFactory(
            new PsrResponseFactory(),
            new ResponseFactory(
                new DataResponseFactory(
                    new PsrResponseFactory(),
                    new StreamFactory(),
                ),
                $this->createTranslator(),
            ),
            $this->createTranslator(),
            new Injector(new Container()),
        ))->create();
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
