<?php

declare(strict_types=1);

// PSR Interfaces
use Psr\Log\LoggerInterface;

// Vendor Layer
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Log\Logger;
use Yiisoft\Log\StreamTarget;
use Yiisoft\Log\Target\File\FileTarget;

// @var array $params

return [
    LoggerInterface::class => [
        'class' => Logger::class,
        '__construct()' => [
            'targets' => ReferencesArray::from([
                FileTarget::class,
                StreamTarget::class,
            ]),
        ],
    ],
];
