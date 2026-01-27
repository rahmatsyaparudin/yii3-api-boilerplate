# Backup and Restore Procedure

## Overview

This document outlines the backup and restore procedures for the Yii3 API application, focusing on database backups and application configuration.

## Prerequisites

- Access to the PostgreSQL database
- Sufficient storage for backups
- `pg_dump` and `psql` command-line tools installed
- Application environment variables configured

## Environment Variables

Ensure the following environment variables are set in your `.env` file:

```env
DB_DSN=pgsql:host=localhost;port=5432;dbname=yii3_api
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
BACKUP_DIR=/var/backups/yii3-api
RETENTION_DAYS=30
```

## Backup Procedures

### 1. Automated Daily Backup

Create a cron job for daily backups:

```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /path/to/backup-script.sh
```

### 2. Manual Backup

#### Full Database Backup
```bash
# Create backup directory
mkdir -p $BACKUP_DIR/$(date +%Y%m%d)

# Dump database
pg_dump $DB_DSN --username=$DB_USERNAME --no-password --clean --if-exists \
  --file=$BACKUP_DIR/$(date +%Y%m%d)/yii3_api_$(date +%H%M%S).sql

# Compress backup
gzip $BACKUP_DIR/$(date +%Y%m%d)/yii3_api_$(date +%H%M%S).sql
```

#### Schema-only Backup
```bash
pg_dump $DB_DSN --username=$DB_USERNAME --no-password --schema-only \
  --file=$BACKUP_DIR/$(date +%Y%m%d)/schema_$(date +%H%M%S).sql
```

#### Data-only Backup
```bash
pg_dump $DB_DSN --username=$DB_USERNAME --no-password --data-only \
  --file=$BACKUP_DIR/$(date +%Y%m%d)/data_$(date +%H%M%S).sql
```

### 3. Application Configuration Backup

```bash
# Backup configuration files
tar -czf $BACKUP_DIR/$(date +%Y%m%d)/config_$(date +%H%M%S).tar.gz \
  config/ \
  .env \
  composer.json \
  composer.lock
```

## Restore Procedures

### 1. Restore from Full Backup

```bash
# Extract backup if compressed
gunzip -c $BACKUP_DIR/20240101/yii3_api_020000.sql.gz > /tmp/restore.sql

# Restore database
psql $DB_DSN --username=$DB_USERNAME --file=/tmp/restore.sql

# Clean up
rm /tmp/restore.sql
```

### 2. Restore Schema Only
```bash
psql $DB_DSN --username=$DB_USERNAME --file=$BACKUP_DIR/20240101/schema_020000.sql
```

### 3. Restore Data Only
```bash
psql $DB_DSN --username=$DB_USERNAME --file=$BACKUP_DIR/20240101/data_020000.sql
```

### 4. Restore Configuration
```bash
# Extract configuration backup
tar -xzf $BACKUP_DIR/20240101/config_020000.tar.gz -C /tmp/

# Copy files back (with caution)
cp /tmp/config/* /path/to/your/app/config/
cp /tmp/.env /path/to/your/app/
```

## Backup Script Example

Create `/usr/local/bin/yii3-api-backup.sh`:

```bash
#!/bin/bash

# Load environment
source .env

# Create backup directory
BACKUP_PATH="$BACKUP_DIR/$(date +%Y%m%d)"
mkdir -p $BACKUP_PATH

# Database backup
pg_dump $DB_DSN --username=$DB_USERNAME --no-password --clean --if-exists \
  --file=$BACKUP_PATH/yii3_api_$(date +%H%M%S).sql

# Compress
gzip $BACKUP_PATH/yii3_api_$(date +%H%M%S).sql

# Config backup
tar -czf $BACKUP_PATH/config_$(date +%H%M%S).tar.gz config/ .env composer.json composer.lock

# Cleanup old backups (keep last $RETENTION_DAYS days)
find $BACKUP_DIR -type d -mtime +$RETENTION_DAYS -exec rm -rf {} \;

# Log
echo "Backup completed: $BACKUP_PATH" >> /var/log/yii3-api-backup.log
```

Make it executable:
```bash
chmod +x /usr/local/bin/yii3-api-backup.sh
```

## Testing Backups

### 1. Verify Backup Integrity
```bash
# Test SQL file integrity
zcat $BACKUP_DIR/20240101/yii3_api_020000.sql.gz | head -20

# Test tar file integrity
tar -tzf $BACKUP_DIR/20240101/config_020000.tar.gz | head -10
```

### 2. Test Restore on Staging
1. Set up a staging database
2. Restore the latest backup to staging
3. Run application tests
4. Verify data integrity

## Disaster Recovery

### 1. Complete System Recovery
1. Provision new server
2. Install required dependencies (PHP, PostgreSQL, Nginx)
3. Clone application repository
4. Restore configuration from latest backup
5. Restore database from latest backup
6. Run migrations (if needed)
7. Test application functionality

### 2. Point-in-Time Recovery (if WAL archiving is enabled)
```bash
# Restore from base backup
pg_basebackup -D /var/lib/postgresql/backup/base -h localhost -U postgres

# Apply WAL logs until desired time
pg_ctl start -D /var/lib/postgresql/backup/base
```

## Monitoring and Alerts

### 1. Backup Verification
- Monitor backup file creation
- Verify backup file sizes
- Check backup logs for errors

### 2. Storage Monitoring
- Monitor disk space in backup directory
- Set alerts when usage exceeds 80%

### 3. Restore Testing
- Schedule monthly restore tests
- Document test results
- Update procedures based on test findings

## Security Considerations

1. **Encryption**: Consider encrypting backups at rest
2. **Access Control**: Limit backup file access to authorized users
3. **Off-site Storage**: Store copies of critical backups off-site
4. **Password Protection**: Use password-protected compression for sensitive data

## Retention Policy

- **Daily backups**: Keep for 30 days
- **Weekly backups**: Keep for 12 weeks
- **Monthly backups**: Keep for 12 months
- **Yearly backups**: Keep for 7 years

Adjust retention based on:
- Regulatory requirements
- Storage capacity
- Business needs

## Troubleshooting

### Common Issues

1. **Permission denied**: Check database user permissions
2. **Disk full**: Monitor and clean up old backups
3. **Connection failed**: Verify database connectivity
4. **Corrupted backup**: Test backup integrity regularly

### Recovery Commands

```bash
# Check database connection
psql $DB_DSN --username=$DB_USERNAME -c "SELECT version();"

# List recent backups
ls -la $BACKUP_DIR/$(date +%Y%m%d)/

# Check backup size
du -sh $BACKUP_DIR/$(date +%Y%m%d)/
```
