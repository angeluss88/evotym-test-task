# Symfony Microservices Task

This repository contains a small Symfony 6.4 microservices monorepo for the technical task.

Current structure:

- `product-service`: Symfony application for product management
- `order-service`: Symfony application for order management
- `shared-bundle`: reusable shared package for common code
- `docker-compose.yml`: local infrastructure and app runtime

## Stack

- Symfony `6.4`
- PHP `8.4`
- PostgreSQL `16`
- RabbitMQ with management UI
- Docker Compose for local development

## Services And Ports

- `product-service`: `http://localhost:8001`
- `order-service`: `http://localhost:8002`
- `rabbitmq`: `amqp://localhost:5672`
- RabbitMQ management UI: `http://localhost:15672`
- `product-db`: `localhost:5433`
- `order-db`: `localhost:5434`
- `adminer` optional: `http://localhost:8080`

## Run With Docker

Start the main stack:

```bash
docker compose up --build
```

Start the stack in background:

```bash
docker compose up --build -d
```

Start Adminer too:

```bash
docker compose --profile tools up --build
```

Stop the stack:

```bash
docker compose down
```

## Local Run Without Docker

Each service can also run directly on the host machine.

Example:

```bash
cd product-service
composer install
symfony server:start
```

or:

```bash
cd product-service
composer install
php -S 127.0.0.1:8000 -t public
```

Do the same for `order-service`.

When running outside Docker, the default `.env` values expect:

- PostgreSQL for product service on `localhost:5433`
- PostgreSQL for order service on `localhost:5434`
- RabbitMQ on `localhost:5672`

