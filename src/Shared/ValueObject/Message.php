<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

/**
 * Message Value Object
 * 
 * This readonly class represents a translatable message with key, parameters,
 * and optional domain for internationalization. It provides type-safe handling
 * of localized messages throughout the application.
 * 
 * @package App\Shared\ValueObject
 * 
 * @example
 * // Basic message with key only
 * $message = new Message(key: 'user.created');
 * echo $message->getKey(); // 'user.created'
 * 
 * @example
 * // Message with parameters using named arguments
 * $message = new Message(
 *     key: 'user.welcome',
 *     params: ['name' => 'John', 'role' => 'admin']
 * );
 * 
 * @example
 * // Message with domain for specific translation context
 * $message = new Message(
 *     key: 'validation.required',
 *     params: ['field' => 'email'],
 *     domain: 'validation'
 * );
 * 
 * @example
 * // In exception throwing
 * throw new BadRequestException(
 *     translate: new Message(
 *         key: 'auth.invalid_credentials',
 *         domain: 'auth'
 *     )
 * );
 * 
 * @example
 * // In service layer
 * public function sendWelcomeEmail(User $user): void
 * {
 *     $message = new Message(
 *         key: 'email.welcome',
 *         params: ['name' => $user->getName()],
 *         domain: 'email'
 *     );
 *     $this->translator->trans($message->getKey(), $message->getParams(), $message->getDomain());
 * }
 */
final readonly class Message
{
    /**
     * Message constructor
     * 
     * Creates a new Message value object with translation key, parameters,
     * and optional domain for internationalization support.
     * 
     * @param string $key Translation key identifier
     * @param array $params Parameters for message interpolation (default: [])
     * @param string|null $domain Translation domain for context (default: null)
     * 
     * @example
     * // Simple message
     * $message = new Message(key: 'success');
     * 
     * @example
     * // Message with parameters using named arguments
     * $message = new Message(
     *     key: 'user.created',
     *     params: ['username' => 'john_doe', 'timestamp' => time()]
     * );
     * 
     * @example
     * // Message with domain
     * $message = new Message(
     *     key: 'validation.email.required',
     *     params: ['field' => 'email'],
     *     domain: 'validation'
     * );
     * 
     * @example
     * // Error message for exceptions
     * $message = new Message(
     *     key: 'error.not_found',
     *     params: ['resource' => 'User', 'id' => 123],
     *     domain: 'errors'
     * );
     */
    public function __construct(
        public string $key,
        public array $params = [],
        public ?string $domain = null
    ) {}

    /**
     * Get the message key
     * 
     * Returns the translation key identifier used for looking up
     * the message in translation files or services.
     * 
     * @return string Translation key
     * 
     * @example
     * // Get translation key
     * $key = $message->getKey();
     * $translated = $translator->trans($key, $message->getParams());
     * 
     * @example
     * // In translation service
     * public function translate(Message $message): string
     * {
     *     return $this->translator->trans(
     *         $message->getKey(),
     *         $message->getParams(),
     *         $message->getDomain()
     *     );
     * }
     * 
     * @example
     * // For logging
     * $this->logger->info('Translation key', ['key' => $message->getKey()]);
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get message parameters
     * 
     * Returns the parameters array used for message interpolation
     * in translation strings (e.g., {name}, {count}, etc.).
     * 
     * @return array Message parameters
     * 
     * @example
     * // Get parameters for translation
     * $params = $message->getParams();
     * $translated = str_replace(array_keys($params), array_values($params), $template);
     * 
     * @example
     * // In template rendering
     * public function renderMessage(Message $message): string
     * {
     *     $template = $this->getTemplate($message->getKey());
     *     return $this->interpolate($template, $message->getParams());
     * }
     * 
     * @example
     * // For debugging
     * $params = $message->getParams();
     * $this->logger->debug('Message parameters', $params);
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get the message domain
     * 
     * Returns the translation domain used for organizing translations
     * into logical groups (e.g., 'validation', 'auth', 'errors').
     * 
     * @return string|null Translation domain or null
     * 
     * @example
     * // Get domain for translation
     * $domain = $message->getDomain();
     * $translated = $translator->trans($message->getKey(), $message->getParams(), $domain);
     * 
     * @example
     * // In translation service with domain fallback
     * public function translate(Message $message): string
     * {
     *     $domain = $message->getDomain() ?? 'messages';
     *     return $this->translator->trans($message->getKey(), $message->getParams(), $domain);
     * }
     * 
     * @example
     * // Conditional translation based on domain
     * $domain = $message->getDomain();
     * if ($domain === 'validation') {
     *     return $this->formatValidationMessage($message);
     * }
     * 
     * @example
     * // For caching translations by domain
     * $cacheKey = $message->getDomain() . ':' . $message->getKey();
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }
}