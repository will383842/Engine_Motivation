#!/bin/bash
set -e
echo "Rolling back..."
cd /opt/motivation-engine
docker compose -f docker-compose.prod.yml down
git checkout HEAD~1
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
echo "Rollback complete!"
