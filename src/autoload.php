<?php

declare(strict_types=1);

use App\Environment;
use Dotenv\Dotenv;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

// API: Load environment variables from .env file
$root = \dirname(__DIR__);
if (\file_exists($root . '/.env')) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->load();
}

Environment::prepare();
