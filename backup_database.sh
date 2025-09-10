#!/bin/bash

# Database Backup Script for SPP YASMU
# Run this script daily at 02:00 WIB

# Configuration
DB_NAME="spp_yasmu"
DB_USER="root"
DB_PASS=""
BACKUP_DIR="/backup/database"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="spp_yasmu_${DATE}.sql"
RETENTION_DAILY=30
RETENTION_MONTHLY=12

# Create backup directory if not exists
mkdir -p $BACKUP_DIR

# Create daily backup
echo "Creating daily backup: $BACKUP_FILE"
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$BACKUP_FILE

# Compress backup file
gzip $BACKUP_DIR/$BACKUP_FILE
echo "Backup compressed: $BACKUP_FILE.gz"

# Clean old daily backups (keep last 30 days)
find $BACKUP_DIR -name "spp_yasmu_*.sql.gz" -mtime +$RETENTION_DAILY -delete
echo "Cleaned old daily backups"

# Create monthly backup on first day of month
if [ $(date +%d) -eq 01 ]; then
    MONTHLY_BACKUP="spp_yasmu_monthly_$(date +%Y%m).sql"
    echo "Creating monthly backup: $MONTHLY_BACKUP"
    mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$MONTHLY_BACKUP
    gzip $BACKUP_DIR/$MONTHLY_BACKUP
    
    # Clean old monthly backups (keep last 12 months)
    find $BACKUP_DIR -name "spp_yasmu_monthly_*.sql.gz" -mtime +$((RETENTION_MONTHLY * 30)) -delete
    echo "Cleaned old monthly backups"
fi

# Upload to cloud storage (optional)
# You can add cloud storage upload commands here
# Example: aws s3 cp $BACKUP_DIR/$BACKUP_FILE.gz s3://your-bucket/backups/

echo "Backup completed successfully at $(date)"
echo "Backup file: $BACKUP_FILE.gz"
echo "Backup size: $(du -h $BACKUP_DIR/$BACKUP_FILE.gz | cut -f1)"
