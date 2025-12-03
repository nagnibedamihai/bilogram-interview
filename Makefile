.PHONY: help build up down restart logs shell composer artisan migrate fresh test

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Initial setup - install Laravel and configure
	./setup.sh

build: ## Build Docker containers
	docker compose build

up: ## Start all services
	docker compose up -d

down: ## Stop all services
	docker compose down

restart: ## Restart all services
	docker compose restart

logs: ## Show logs (use with service name: make logs service=app)
	@if [ -z "$(service)" ]; then \
		docker compose logs -f; \
	else \
		docker compose logs -f $(service); \
	fi

shell: ## Access app container bash
	docker compose exec app bash

db-shell: ## Access database shell
	docker compose exec db psql -U laravel -d data_processing

composer: ## Run composer command (use: make composer cmd="require package")
	docker compose exec app composer $(cmd)

artisan: ## Run artisan command (use: make artisan cmd="migrate")
	docker compose exec app php artisan $(cmd)

migrate: ## Run database migrations
	docker compose exec app php artisan migrate

fresh: ## Fresh migration with seed
	docker compose exec app php artisan migrate:fresh --seed

test: ## Run tests
	docker compose exec app php artisan test

cache-clear: ## Clear all caches
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear

queue-work: ## Start queue worker
	docker compose exec app php artisan queue:work

status: ## Show container status
	docker compose ps

clean: ## Remove all containers and volumes
	docker compose down -v
	rm -rf src

permissions: ## Fix storage permissions
	sudo chmod -R 777 src/storage src/bootstrap/cache
