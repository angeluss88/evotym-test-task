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

The RabbitMQ consumer for `order-service` runs automatically as the `order-consumer` container, so no separate `messenger:consume` command is needed during normal Docker usage.

Start Adminer too:

```bash
docker compose --profile tools up --build
```

Stop the stack:

```bash
docker compose down
```

## Manual Test Steps

1. Start the stack:

```bash
docker compose up --build -d
```

2. Create a product in `product-service`:

```bash
curl -X POST http://localhost:8001/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Coffee Mug","price":12.99,"quantity":100}'
```

3. Verify that the product was synchronized to `order-service` by creating an order:

```bash
curl -X POST http://localhost:8002/orders \
  -H "Content-Type: application/json" \
  -d '{"productId":"<PRODUCT_ID>","customerName":"John Doe","quantityOrdered":2}'
```

4. Check created orders:

```bash
curl http://localhost:8002/orders
```

## Automated Tests

Test structure:

- `product-service/tests`: focused functional tests for product creation and validation
- `order-service/tests`: focused functional and integration tests for order creation rules and product synchronization
- `tests/e2e`: root-level end-to-end tests for cross-service behavior

Run product-service tests:

```bash
make test-product
```

or:

```bash
cd product-service && ./vendor/bin/simple-phpunit
```

Run order-service tests:

```bash
make test-order
```

or:

```bash
cd order-service && ./vendor/bin/simple-phpunit
```

Run end-to-end tests:

```bash
make test-e2e
```

or:

```bash
php tests/e2e/run.php
```

The end-to-end tests validate the full product-to-order flow across services, including successful ordering, insufficient quantity failure, and missing product failure.

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

