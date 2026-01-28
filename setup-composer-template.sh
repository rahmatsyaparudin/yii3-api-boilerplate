#!/bin/bash

echo "🔧 Setting up composer.json for template..."

# Backup original composer.json
cp composer.json composer-original.json

# Create template-friendly composer.json
cat > composer.json << 'EOF'
{
    "name": "rahmatsyaparudin/yii3-api-boilerplate",
    "description": "Yii3 API boilerplate with DDD and Optimistic Locking",
    "type": "project-template",
    "keywords": ["yii3", "api", "ddd", "boilerplate", "optimistic-locking", "template"],
    "license": "MIT",
    "authors": [
        {
            "name": "Rahmat Syaparudin",
            "email": "rahmat.syaparudin@example.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "yiisoft/yii-core": "^1.0",
        "yiisoft/yii-console": "^1.0",
        "yiisoft/yii-web": "^1.0",
        "yiisoft/yii-db": "^1.0",
        "yiisoft/yii-di": "^1.0",
        "yiisoft/yii-router": "^1.0",
        "yiisoft/yii-middleware": "^1.0",
        "yiisoft/yii-validator": "^1.0",
        "yiisoft/yii-http": "^1.0",
        "yiisoft/yii-jwt": "^1.0",
        "yiisoft/psr-log": "^1.0",
        "psr/log": "^3.0",
        "psr/http-message": "^1.0",
        "psr/http-server-handler": "^1.0",
        "firebase/php-jwt": "^6.0",
        "symfony/console": "^6.0",
        "symfony/filesystem": "^6.0",
        "symfony/finder": "^6.0"
    },
    "require-dev": {
        "yiisoft/yii-dev-tool": "^1.0",
        "phpunit/phpunit": "^10.0",
        "codeception/codeception": "^5.0",
        "infection/infection": "^0.26",
        "vimeo/psalm": "^5.0",
        "friendsofphp/php-cs-fixer": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Tests\\": "tests"
        }
    },
    "scripts": {
        "post-create-project-cmd": [
            "php yii template:setup"
        ],
        "serve": "php -S localhost:8080 -t public",
        "test": "codecept run",
        "cs-fix": "php-cs-fixer fix",
        "psalm": "psalm",
        "infection": "infection"
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "yiisoft/yii-dev-tool": true,
            "infection/extension-installer": true
        }
    },
    "extra": {
        "branch-alias": {
            "dev-master": "1.0-dev"
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
EOF

echo "✅ composer.json updated for template"
echo "📁 Type: project-template"
echo "🏷️  Ready for Packagist submission"
