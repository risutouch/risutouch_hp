#!/bin/bash

# バックアップ設定
APP_DIR="/home/c6276796/public_html/risutouch.com/app/harairo"
BACKUP_DIR="${APP_DIR}/backups"
DATE_TIME=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="${BACKUP_DIR}/backup_${DATE_TIME}"

# バックアップディレクトリの作成
mkdir -p "${BACKUP_DIR}"
mkdir -p "${BACKUP_PATH}"

# dataディレクトリのバックアップ
cp -r "${APP_DIR}/data" "${BACKUP_PATH}/"

echo "Backup completed: ${BACKUP_PATH}"

# 30日以上前のバックアップを削除
find "${BACKUP_DIR}" -maxdepth 1 -type d -name "backup_*" -mtime +30 -exec rm -rf {} \;

echo "Old backups removed (older than 30 days)"
