# 🚀 Deployment Guide: Yii3 API Boilerplate

## 📦 Publish ke Packagist

### **Prerequisites**
- ✅ GitHub repository ready
- ✅ composer.json configured as project-template
- ✅ Git tag created

### **Step-by-Step**

#### **1. Repository Setup**
```bash
# Clean repository
rm -rf vendor/ runtime/ .git/ composer.lock .env

# Create .gitignore
cat > .gitignore << EOF
/vendor/
/runtime/
/node_modules/
.env
.env.local
.env.*.local
composer.lock
.phpunit.cache/
.DS_Store
EOF

# Init and push
git init
git add .
git commit -m "Yii3 API Boilerplate Template v1.0.0"
git remote add origin https://github.com/rahmatsyaparudin/yii3-api-boilerplate.git
git push -u origin main
```

#### **2. Create Release**
```bash
# Tag release
git tag -a v1.0.0 -m "Yii3 API Boilerplate v1.0.0"
git push origin v1.0.0

# Also push to GitHub (creates release)
# Go to GitHub → Releases → Create new release
# Tag: v1.0.0
# Title: Yii3 API Boilerplate v1.0.0
# Description: Initial release of Yii3 API boilerplate template
```

#### **3. Submit to Packagist**
1. Go to: https://packagist.org/packages/submit
2. Login with GitHub
3. Enter repository URL: `https://github.com/rahmatsyaparudin/yii3-api-boilerplate.git`
4. Click "Check"
5. Click "Submit"

#### **4. Verify Package**
```bash
# Test installation
composer create-project rahmatsyaparudin/yii3-api-boilerplate my-test-api

# Check package info
composer show rahmatsyaparudin/yii3-api-boilerplate
```

## 🧪 Testing Template

### **Basic Test**
```bash
# Create new project
composer create-project rahmatsyaparudin/yii3-api-boilerplate my-api

cd my-api

# Setup
composer install
cp .env.example .env

# Verify structure
ls src/Domain/
ls src/Infrastructure/
ls src/Api/

# Test generator
php yii template:generate test-project /tmp/output
```

### **Advanced Test**
```bash
# Test with optimistic locking
php yii template:generate test-api /tmp --include-optimistic-lock

# Verify optimistic locking files
ls /tmp/test-api/src/Domain/Shared/ValueObject/LockVersion.php
ls /tmp/test-api/src/Domain/Shared/Concerns/Entity/OptimisticLock.php
```

## 🔄 Maintenance

### **Update Template**
```bash
# Make changes
git add .
git commit -m "Update template features"

# Tag new version
git tag v1.1.0
git push origin v1.1.0

# Packagist auto-updates (GitHub webhook)
```

### **Version Management**
```bash
# List versions
git tag -l

# Delete tag (if needed)
git tag -d v1.0.0
git push origin :refs/tags/v1.0.0

# Create new tag
git tag -a v1.1.0 -m "Version 1.1.0"
git push origin v1.1.0
```

## 📚 Usage Examples

### **Simple API Project**
```bash
composer create-project rahmatsyaparudin/yii3-api-boilerplate user-api
cd user-api
composer install
cp .env.example .env
./yii migrate
./yii serve
```

### **Enterprise API**
```bash
composer create-project rahmatsyaparudin/yii3-api-boilerplate enterprise-api
cd enterprise-api

# Generate entities
php yii simple-generate crud User Product --with-lock-version

# Setup database
./yii migrate
./yii serve
```

### **Microservice**
```bash
composer create-project rahmatsyaparudin/yii3-api-boilerplate user-service
cd user-service

# Generate minimal setup
php yii simple-generate entity User
php yii simple-generate repository User
php yii simple-generate service User
php yii simple-generate api User
```

## 🔧 Troubleshooting

### **Common Issues**

#### **Packagist Not Updating**
```bash
# Force update Packagist
curl https://packagist.org/github/rahmatsyaparudin/yii3-api-boilerplate

# Or wait 5-10 minutes for auto-update
```

#### **Template Not Working**
```bash
# Check composer.json type
composer config type

# Should be: project-template
# If not, update:
composer config type project-template
```

#### **Missing Files**
```bash
# Verify all files are committed
git status
git add .
git commit -m "Add missing template files"
git push
```

#### **Permission Issues**
```bash
# Fix file permissions
chmod +x yii
chmod +x setup-composer-template.sh
```

---

**Status: 🎯 Deployment guide ready! Template siap dipublish ke Packagist!**
