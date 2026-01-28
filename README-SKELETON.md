# Skeleton Installation Guide

## Quick Start

After creating a project from this boilerplate, run:

```bash
composer install-skeleton
```

## What it does

The `install-skeleton` script will:

1. **Update composer.json** - Convert from template to project
2. **Apply string replacements** - Replace boilerplate names with your project name
3. **Setup environment** - Create `.env` from `.env.example`
4. **Clean up** - Remove boilerplate-specific files

## Manual Installation

If you prefer to set up manually:

1. Update `composer.json`:
   - Change `name` to your vendor/project
   - Change `type` to `project`
   - Remove boilerplate metadata

2. Replace strings in all files:
   - `yii3-api` → your project name
   - `Yii3Api` → YourProjectName
   - `YII3_API` → YOUR_PROJECT_NAME
   - `Example` → your default entity name

3. Setup environment:
   ```bash
   cp .env.example .env
   ```

4. Install and run:
   ```bash
   composer install
   php yii migrate
   php yii serve
   ```

## Available Commands

After installation:

```bash
# Start development server
composer run serve

# Run migrations
composer run migrate

# Generate CRUD
php yii simple-generate crud Product

# Generate from template
php yii template:generate my-api /tmp
```
