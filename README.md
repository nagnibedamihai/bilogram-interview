# Data Processing Service

A Laravel-based microservice for processing standardized data records from multiple sources with aggregation and querying capabilities.

## 📋 Requirements

- Docker Desktop for Mac
- Docker Compose
- Git

## 🚀 Quick Setup

### 1. Clone or Navigate to Project Directory

```bash
cd /path/to/data-processing-service
```

### 2. Build and Start Docker Containers

```bash
docker-compose build
docker-compose up -d
```

### 3. Install Laravel and Configure

Run the setup script:

```bash
./setup.sh
```

This will:
- Install Laravel via Composer
- Configure PostgreSQL database connection
- Configure Redis for caching and queues
- Generate application key
- Run initial migrations

### 4. Verify Installation

Visit `http://localhost:8080` - you should see the Laravel welcome page.

## 🗄️ Database Access

### Via Command Line (psql)

```bash
docker-compose exec db psql -U laravel -d data_processing
```

### Via PHPStorm

1. Open PHPStorm
2. Go to Database Tool Window (View → Tool Windows → Database)
3. Click "+" → Data Source → PostgreSQL
4. Configure connection:
   - **Host:** localhost
   - **Port:** 5432
   - **Database:** data_processing
   - **User:** laravel
   - **Password:** secret
5. Test Connection and Save

### Via TablePlus or Other GUI Tools

- **Host:** localhost
- **Port:** 5432
- **Database:** data_processing
- **User:** laravel
- **Password:** secret

## 🛠️ Development Commands

### Laravel Commands

```bash
# Access the application container
docker-compose exec app bash

# Run migrations
docker-compose exec app php artisan migrate

# Create a new migration
docker-compose exec app php artisan make:migration create_records_table

# Create a new model
docker-compose exec app php artisan make:model Record -m

# Create a controller
docker-compose exec app php artisan make:controller RecordController

# Create a request validation
docker-compose exec app php artisan make:request StoreRecordRequest

# Run queue worker
docker-compose exec app php artisan queue:work

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
```

### Docker Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f db

# Rebuild containers
docker-compose build --no-cache

# Remove all containers and volumes
docker-compose down -v
```

### Composer Commands

```bash
# Install dependencies
docker-compose exec app composer install

# Update dependencies
docker-compose exec app composer update

# Add a package
docker-compose exec app composer require vendor/package
```

## 📁 Project Structure

```
data-processing-service/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── local.ini
├── src/                    # Laravel application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── ...
├── docker-compose.yml
├── Dockerfile
├── setup.sh
└── README.md
```

## 🔌 Services

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 8080 | Web server |
| PostgreSQL | 5432 | Database |
| Redis | 6379 | Cache & Queue |

## 📊 Assignment Implementation

The service will implement:

1. **Data Ingestion**: Accept ~100,000 records/hour with idempotency
2. **Aggregation API**: Query records with filtering and grouping
3. **Notification Service**: Emit messages with summaries
4. **Alert Service**: Trigger alerts based on thresholds

## 🧪 Testing

```bash
# Run tests
docker-compose exec app php artisan test

# Run specific test
docker-compose exec app php artisan test --filter=RecordTest
```

## 🐛 Troubleshooting

### Port Already in Use

If ports 8080 or 5432 are already in use, modify `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "8081:80"  # Change 8080 to 8081

db:
  ports:
    - "5433:5432"  # Change 5432 to 5433
```

### Permission Issues

```bash
sudo chmod -R 777 src/storage src/bootstrap/cache
```

### Database Connection Issues

Verify database is running:

```bash
docker-compose ps
docker-compose logs db
```

### Clear Everything and Start Fresh

```bash
docker-compose down -v
rm -rf src
./setup.sh
```

## 📝 Next Steps

1. ✅ Setup complete
2. Create database migrations for records table
3. Implement API endpoints for data ingestion
4. Implement aggregation queries
5. Setup message queues for notifications and alerts
6. Add tests

## 📚 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Docker Documentation](https://docs.docker.com/)
