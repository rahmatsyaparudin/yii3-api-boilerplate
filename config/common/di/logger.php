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
    'log.target.security' => [
        'class' => FileTarget::class,
        '__construct()' => [
            'file' => '@runtime/logs/security/security.log',
            'categories' => ['security'],
            'maxFileSize' => 1024,
            'maxLogFiles' => 5,
        ],
    ],

    LoggerInterface::class => [
        'class' => Logger::class,
        '__construct()' => [
            'targets' => ReferencesArray::from([
                FileTarget::class,
                StreamTarget::class,
                'log.target.security',
            ]),
        ],
    ],
];
