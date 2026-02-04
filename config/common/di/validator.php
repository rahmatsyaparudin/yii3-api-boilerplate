<?php

declare(strict_types=1);

use App\Shared\Validation\Rules\UniqueValueHandler;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\Validator\RuleHandlerResolverInterface;
use Yiisoft\Validator\RuleHandlerResolver\SimpleRuleHandlerContainer;
use Yiisoft\Validator\Validator;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;

return [
    // 1. Use SimpleRuleHandlerContainer with pre-registered handlers
    RuleHandlerResolverInterface::class => static function (ContainerInterface $container) {
        return new SimpleRuleHandlerContainer([
            UniqueValueHandler::class => $container->get(UniqueValueHandler::class),
        ]);
    },

    // 2. Configure UniqueValueHandler with dependencies
    UniqueValueHandler::class => [
        '__construct()' => [
            'db' => Reference::to(ConnectionInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],

    // 3. Configure Validator
    ValidatorInterface::class => Validator::class,
];