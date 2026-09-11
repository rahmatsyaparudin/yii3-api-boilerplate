<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;

/** @var array $params */

// Core translator binding. Project-owned bindings may be merged from
// config/common/translator.php and can override these defaults.
$projectBindings = \dirname(__DIR__) . '/translator.php';

return array_merge([
    TranslatorInterface::class => static function () {
        $translator = new Translator('en');

        $messageSource = new MessageSource(__DIR__ . '/../../../resources/messages');
        $formatter     = new IntlMessageFormatter();

        $translator->addCategorySources(
            new CategorySource('app', $messageSource, $formatter),
            new CategorySource('validation', $messageSource, $formatter),
            new CategorySource('error', $messageSource, $formatter),
            new CategorySource('success', $messageSource, $formatter),
        );

        return $translator;
    },
], file_exists($projectBindings) ? require $projectBindings : []);
