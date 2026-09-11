<?php

declare(strict_types=1);

/**
 * Optional project-specific translator overrides/additions.
 *
 * Definitions here are merged into config/common/di/translator-di.php and
 * can override the default TranslatorInterface binding.
 *
 * Example:
 *
 *   use Yiisoft\Translator\TranslatorInterface;
 *   use Yiisoft\Translator\Translator;
 *
 *   return [
 *       TranslatorInterface::class => static fn () => new Translator('id'),
 *   ];
 */

return [];
