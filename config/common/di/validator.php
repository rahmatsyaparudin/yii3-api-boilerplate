<?php

declare(strict_types=1);

use App\Shared\Core\Validation\Rules\UniqueValueHandler;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\RuleHandlerResolver\SimpleRuleHandlerContainer;
use Yiisoft\Validator\RuleHandlerResolverInterface;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;

return [
    // 1. Use SimpleRuleHandlerContainer with pre-registered handlers
    RuleHandlerResolverInterface::class => static fn (ContainerInterface $container) => new SimpleRuleHandlerContainer([
        UniqueValueHandler::class => $container->get(UniqueValueHandler::class),
    ]),

    // 2. Configure UniqueValueHandler with dependencies
    UniqueValueHandler::class => [
        '__construct()' => [
            'db'         => Reference::to(ConnectionInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],

    // 3. Configure Validator
    ValidatorInterface::class => Validator::class,
];
