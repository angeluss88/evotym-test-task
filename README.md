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

## Product API Shape

The `product-service` API uses the same four product fields everywhere:

- `id` as UUID
- `name`
- `price`
- `quantity`

Example product response:

```json
{
  "id": "018f4b0c-8ee8-7d15-bc28-0c65c8e0e9aa",
  "name": "Coffee Mug",
  "price": 12.99,
  "quantity": 100
}
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

## Security Considerations

Authentication and authorization are intentionally not implemented in this technical task because they are outside the requested scope and not necessary to demonstrate the core microservice design. The focus here is on service boundaries, shared contracts, persistence, and asynchronous communication through RabbitMQ. In a production system, access control would typically be handled with JWT-based authentication, OAuth2/OpenID Connect, and often an API Gateway or dedicated identity provider in front of the services.

