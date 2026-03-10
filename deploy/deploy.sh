#!/bin/bash
set -e

# ============================================================
# Deploy Engine_Motivation to Hetzner server
# Usage: ./deploy/deploy.sh
# ============================================================

SERVER="root@46.62.168.55"
REMOTE_DIR="/opt/engine-motivation"
REPO_URL="https://github.com/will383842/Engine_Motivation.git"

echo "=== 1. Preparing server directory ==="
ssh $SERVER "mkdir -p $REMOTE_DIR"

echo "=== 2. Cloning/pulling latest code ==="
ssh $SERVER "
  if [ -d $REMOTE_DIR/.git ]; then
    cd $REMOTE_DIR && git pull origin main
  else
    git clone $REPO_URL $REMOTE_DIR
    cd $REMOTE_DIR
  fi
"

echo "=== 3. Copying production files ==="
scp .env.production $SERVER:$REMOTE_DIR/.env.production

echo "=== 4. Building and starting containers ==="
ssh $SERVER "
  cd $REMOTE_DIR

  # Build the app image
  docker compose build --no-cache

  # Start all services
  docker compose up -d

  # Wait for DB
  echo 'Waiting for PostgreSQL...'
  sleep 5

  # Run migrations
  docker compose exec -T app php artisan migrate --force

  # Seed admin user and templates
  docker compose exec -T app php artisan db:seed --class=AdminUserSeeder --force
  docker compose exec -T app php artisan db:seed --class=MessageTemplateSeeder --force

  # Cache config
  docker compose exec -T app php artisan config:cache
  docker compose exec -T app php artisan route:cache
"

echo "=== 5. Setting up Nginx reverse proxy ==="
ssh $SERVER "
  # mt-nginx listens on 127.0.0.1:8082
  # The global/backlink Nginx should proxy motivation.life-expat.com to port 8082
  echo 'Motivation Engine nginx running on 127.0.0.1:8082'
  echo 'Ensure DNS A record for motivation.life-expat.com points to 89.167.26.169'
"

echo "=== 6. Health check ==="
ssh $SERVER "
  echo 'Container status:'
  docker ps --filter 'name=mt-' --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
  echo ''
  echo 'Testing health endpoint...'
  curl -s http://127.0.0.1:8082/up | head -5 || echo 'Health check pending...'
"

echo ""
echo "=== DEPLOYMENT COMPLETE ==="
echo "Next steps:"
echo "  1. Point motivation.life-expat.com DNS A record to 46.62.168.55"
echo "  2. Configure SSL via Cloudflare proxy (orange cloud)"
echo "  3. Add reverse proxy rule in bl-nginx or global nginx"
