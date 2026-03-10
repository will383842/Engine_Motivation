#!/bin/bash
set -e
echo "Deploying Motivation Engine..."
cd /opt/motivation-engine
git pull origin main
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml run --rm app php artisan migrate --force
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
echo "Deployment complete!"
