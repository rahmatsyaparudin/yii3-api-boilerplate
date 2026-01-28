# 🚀 Yii3 API Boilerplate Template

Project ini bisa digunakan sebagai template/boilerplate untuk membuat project Yii3 API baru dengan DDD + Optimistic Locking.

## 📋 Cara Menjadi Vendor Package

### **🔧 Method 1: Template Generator (Recommended)**

#### **1. Setup Template Generator**
```bash
# Tambahkan command ke console configuration
# config/console/commands.php
return [
    'template-generate' => \App\Console\TemplateGeneratorCommand::class,
];
```

#### **2. Generate New Project**
```bash
# Generate project baru dari template ini
php yii template:generate my-new-api /path/to/projects --include-optimistic-lock

# Contoh:
php yii template:generate blog-api /Users/dev/projects --include-optimistic-lock
```

#### **3. Options Available**
```bash
--template-path    # Source template path (default: current directory)
--exclude          # Exclude patterns (vendor, .git, runtime, etc)
--replace          # Replace patterns (yii3-api -> project-name)
--include-optimistic-lock  # Include optimistic locking features
```

### **🔧 Method 2: Composer Project Template**

#### **1. Setup sebagai Composer Template**
```bash
# Rename composer.json -> composer-template.json
# Update package type
{
    "name": "rahmatsyaparudin/yii3-api-boilerplate",
    "type": "project-template",
    "description": "Yii3 API boilerplate with DDD and Optimistic Locking"
}
```

#### **2. Publish ke Packagist**
```bash
# Tag release
git tag v1.0.0
git push origin v1.0.0

# Submit ke Packagist
# https://packagist.org/packages/submit
```

#### **3. Create Project dari Template**
```bash
# Install via composer
composer create-project rahmatsyaparudin/yii3-api-boilerplate my-new-api

# Atau dengan specific version
composer create-project rahmatsyaparudin/yii3-api-boilerplate my-new-api:^1.0
```

### **🔧 Method 3: Git Clone & Customize**

#### **1. Clone Template**
```bash
git clone https://github.com/rahmatsyaparudin/yii3-api.git my-new-api
cd my-new-api
```

#### **2. Customize Project**
```bash
# Update composer.json
composer config name your-vendor/my-new-api
composer config description "My New API Project"

# Remove git history
rm -rf .git
git init
git add .
git commit -m "Initial commit"
```

## 🎯 Features yang Bisa Di-inject

### **🔐 Optimistic Locking (Optional)**
```bash
# Include optimistic locking
php yii template:generate my-api /path/to --include-optimistic-lock

# Exclude optimistic locking
php yii template:generate my-api /path/to --exclude-optimistic-lock
```

**Files yang akan di-inject:**
- `src/Domain/Shared/ValueObject/LockVersion.php`
- `src/Domain/Shared/Concerns/Entity/OptimisticLock.php`
- `src/Shared/Exception/OptimisticLockException.php`
- Update entity traits
- Update repository methods
- Update API validation

### **🏗️ DDD Structure**
- **Domain Layer**: Entity, Value Objects, Repository Contracts
- **Application Layer**: Use Cases, Commands, DTOs, Factories
- **Infrastructure Layer**: Repository Implementation, External Services
- **API Layer**: Controllers, Middleware, Presenters

### **🔒 Security Features**
- JWT Authentication
- RBAC Authorization
- Rate Limiting
- Input Sanitization
- Security Headers

### **📊 Observability**
- Structured Logging
- Error Monitoring
- Metrics Collection
- Audit Trail
- Request Tracing

## 📁 Template Structure

```
yii3-api-boilerplate/
├── src/
│   ├── Api/                    # API Layer
│   ├── Application/            # Application Layer
│   ├── Domain/                 # Domain Layer
│   ├── Infrastructure/          # Infrastructure Layer
│   ├── Shared/                 # Shared Components
│   └── Console/                # CLI Commands
├── config/                     # Configuration
├── resources/                  # Resources (i18n)
├── public/                     # Web Root
├── docker/                     # Docker Setup
├── docs/                       # Documentation
└── templates/                  # Template Files
```

## 🛠️ Customization Patterns

### **🔄 String Replacement**
Default patterns yang akan di-replace:
```php
'yii3-api' => '{project_name}',
'Yii3Api' => '{ProjectName}',
'YII3_API' => '{PROJECT_NAME}',
'rahmatsyaparudin/yii3-api' => '{vendor}/{project_name}',
'Brand' => '{Entity}',
'brand' => '{entity}',
```

### **📂 Exclude Patterns**
Default exclude:
```bash
vendor/
.git/
runtime/
tests/
.env
composer.lock
node_modules/
```

### **🎯 Entity Generation**
```bash
# Generate entity dengan optimistic locking
php yii simple-generate entity Product Category --with-lock-version

# Generate full CRUD
php yii simple-generate crud Product Category --with-lock-version
```

## 🚀 Deployment Options

### **🐳 Docker Template**
```bash
# Generate dengan Docker setup
php yii template:generate my-api /path/to --include-docker

# Files yang di-generate:
docker/
├── Dockerfile
├── compose.yml
├── dev/
├── prod/
└── test/
```

### **☁️ Cloud Ready**
```bash
# Include cloud deployment files
php yii template:generate my-api /path/to --include-cloud

# Files yang di-generate:
.github/workflows/
├── deploy.yml
├── quality.yml
└── ci.yml
```

## 📚 Usage Examples

### **🎯 Simple API Project**
```bash
php yii template:generate user-api /projects --include-optimistic-lock
cd /projects/user-api
composer install
cp .env.example .env
./yii migrate
./yii serve
```

### **🏢 Enterprise API**
```bash
php yii template:generate enterprise-api /projects \
  --include-optimistic-lock \
  --include-docker \
  --include-cloud \
  --include-monitoring
```

### **🔬 Microservice**
```bash
php yii template:generate user-service /services \
  --exclude-optimistic-lock \
  --include-docker \
  --include-monitoring
```

## 📋 Next Steps

### **1. Publish Template**
```bash
# Push ke repository
git add .
git commit -m "Add template generator"
git push origin main

# Tag release
git tag v1.0.0
git push origin v1.0.0
```

### **2. Setup CI/CD**
```bash
# Add quality checks
# .github/workflows/quality.yml
name: Quality
on: [push, pull_request]
jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
      - run: composer install
      - run: composer test
```

### **3. Documentation**
```bash
# Update README
# Add getting started guide
# Add API documentation
# Add deployment guide
```

---

**Status: 🎯 Template generator siap digunakan untuk membuat project Yii3 API baru!**
