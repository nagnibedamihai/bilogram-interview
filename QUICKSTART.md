# Quick Start Guide

## Setup Steps (On Your Mac)

### 1. Copy Project Files
Copy all the files to your local machine in a new directory.

### 2. Build and Start
```bash
cd data-processing-service

# Build containers
docker-compose build

# Start services
docker-compose up -d

# Run setup script
./setup.sh
```

### 3. Verify
- Open browser: http://localhost:8080
- You should see Laravel welcome page

## Using Makefile (Recommended)

The Makefile simplifies all commands:

```bash
make setup      # Run initial setup
make up         # Start services
make down       # Stop services
make logs       # View all logs
make shell      # Access app container
make migrate    # Run migrations
make test       # Run tests
```

## Connect PHPStorm to Database

1. Database Tool Window (View → Tool Windows → Database)
2. "+" → Data Source → PostgreSQL
3. Settings:
   - Host: localhost
   - Port: 5432
   - Database: data_processing
   - User: laravel
   - Password: secret
4. Test Connection → OK

## Project Structure After Setup

```
data-processing-service/
├── docker/                 # Docker configs
├── src/                    # Laravel app (created by setup.sh)
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   ├── Models/
│   │   └── Services/
│   ├── config/
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   ├── api.php        # API routes
│   │   └── web.php
│   └── tests/
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── setup.sh
└── README.md
```

## Common Tasks

### Create New Migration
```bash
make artisan cmd="make:migration create_records_table"
```

### Create New Controller
```bash
make artisan cmd="make:controller Api/RecordController --api"
```

### Run Migrations
```bash
make migrate
```

### View Logs
```bash
make logs              # All services
make logs service=app  # Just app
```

### Access Container
```bash
make shell
```

### Run Tests
```bash
make test
```

## Next: Implement Assignment

After setup is complete, you'll create:

1. **Migration** for records table
2. **Model** for Record
3. **Controller** for API endpoints
4. **Jobs** for queue processing
5. **Services** for business logic
6. **Tests** for functionality

All Laravel code goes in the `src/` directory!
