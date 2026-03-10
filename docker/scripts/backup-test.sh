#!/bin/bash
set -e
BACKUP_FILE="/tmp/motivation_backup_test_$(date +%Y%m%d).sql"
echo "Testing backup restore..."
docker compose -f docker-compose.prod.yml exec postgres pg_dump -U motivation motivation_engine > "$BACKUP_FILE"
docker compose -f docker-compose.prod.yml exec postgres psql -U motivation -c "CREATE DATABASE backup_test_db;"
docker compose -f docker-compose.prod.yml exec postgres psql -U motivation -d backup_test_db < "$BACKUP_FILE"
docker compose -f docker-compose.prod.yml exec postgres psql -U motivation -c "DROP DATABASE backup_test_db;"
rm -f "$BACKUP_FILE"
echo "Backup test passed!"
