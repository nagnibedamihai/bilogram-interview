#!/bin/bash

set -e

echo "🚀 Setting up Data Processing Service..."

# Check if src directory exists
if [ -d "src" ]; then
    echo "⚠️  src directory already exists. Skipping Laravel installation."
else
    echo "📦 Installing Laravel..."
    docker compose run --rm app composer create-project --prefer-dist laravel/laravel .
fi

echo "🔧 Setting permissions..."
chmod -R 777 src/storage src/bootstrap/cache 2>/dev/null || docker compose run --rm app chmod -R 777 storage bootstrap/cache

echo "📝 Configuring environment..."
if [ ! -f "src/.env" ]; then
    cp src/.env.example src/.env
fi

# Update .env file with database credentials
sed -i.bak 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' src/.env
sed -i.bak 's/DB_HOST=.*/DB_HOST=db/' src/.env
sed -i.bak 's/DB_PORT=.*/DB_PORT=5432/' src/.env
sed -i.bak 's/DB_DATABASE=.*/DB_DATABASE=data_processing/' src/.env
sed -i.bak 's/DB_USERNAME=.*/DB_USERNAME=laravel/' src/.env
sed -i.bak 's/DB_PASSWORD=.*/DB_PASSWORD=secret/' src/.env

# Configure Redis
sed -i.bak 's/REDIS_HOST=.*/REDIS_HOST=redis/' src/.env
sed -i.bak 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/' src/.env
sed -i.bak 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' src/.env
sed -i.bak 's/SESSION_DRIVER=.*/SESSION_DRIVER=redis/' src/.env

# Remove backup files
rm -f src/.env.bak

echo "🔑 Generating application key..."
docker compose run --rm app php artisan key:generate

echo "🗄️  Running migrations..."
docker compose run --rm app php artisan migrate

echo "✅ Setup complete!"
echo ""
echo "📍 Access the application at: http://localhost:8080"
echo "📍 Database: localhost:5432 (user: laravel, password: secret, db: data_processing)"
echo ""
echo "🛠️  Useful commands:"
echo "  docker compose up -d          # Start all services"
echo "  docker compose down           # Stop all services"
echo "  docker compose exec app bash  # Access app container"
echo "  docker compose exec app php artisan migrate  # Run migrations"
echo "  docker compose logs -f app    # View logs"