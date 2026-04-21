# Symfony Microservices Task

This repository contains a small Symfony 6.4 microservices monorepo for the technical task.

## Architecture Overview

The system consists of two independent services:

- `product-service` is the source of truth for products and publishes product synchronization events
- `order-service` maintains a local copy of products and handles order creation

Services communicate asynchronously via RabbitMQ.

Flow:

1. A product is created in `product-service`
2. A `ProductSyncedMessage` is published to RabbitMQ
3. `order-service` consumes the message and updates its local product table
4. An order is created in `order-service` using the local product read model
5. `order-service` publishes an `OrderCreatedMessage`
6. `product-service` consumes the message, validates quantity, decreases it, and republishes `ProductSyncedMessage`
7. `order-service` consumes the updated `ProductSyncedMessage` and refreshes its local product copy

This design demonstrates:
- separation of concerns
- asynchronous communication
- eventual consistency

## Structure

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
- RabbitMQ AMQP: `amqp://localhost:5672`
- RabbitMQ management UI: `http://localhost:15672`
- `product-db`: `localhost:5433`
- `order-db`: `localhost:5434`
- `adminer` (optional): `http://localhost:8080`

## Run With Docker

Start the stack:

```
docker compose up --build
```

Run in background:

```
docker compose up --build -d
```

On a clean first run, `product-service` and `order-service` automatically execute Doctrine migrations during container startup. The first boot may take a little longer while the databases are initialized.

Start Adminer as well:

```
docker compose --profile tools up --build
```

Stop the stack:

```
docker compose down
```

## First Run

After a clean reset such as:

```
docker compose down -v
```

starting the stack is enough:

```
docker compose up --build -d
```

The API containers run their Doctrine migrations automatically on startup, and the `order-consumer` container starts afterward to process RabbitMQ messages in the background.

## Manual Test Scenario

1. Start the stack:

```
docker compose up --build -d
```

2. The `product-service` and `order-service` consumers are started automatically via Docker Compose.

3. Create a product in `product-service`:

```
curl -X POST http://localhost:8001/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Coffee Mug","price":12.99,"quantity":100}'
```

4. Verify that the product was synchronized to `order-service`.

You can:
- check the database (order-service product table), or
- proceed with order creation below, which implicitly validates synchronization

5. Create an order in `order-service`:

```
curl -X POST http://localhost:8002/orders \
  -H "Content-Type: application/json" \
  -d '{"productId":"<PRODUCT_ID>","customerName":"John Doe","quantityOrdered":2}'
```

Expected result:
- Order is successfully created
- Product quantity is decreased in `product-service`
- `order-service` receives the updated quantity asynchronously through `ProductSyncedMessage`

6. Check created orders:

```
curl http://localhost:8002/orders
```

## Automated Tests

Test structure:

- `product-service/tests`: unit and integration tests for product logic
- `order-service/tests`: unit and integration tests for order logic and product synchronization
- `tests/e2e`: end-to-end tests for cross-service behavior

Test coverage includes:

- Product creation and validation
- Order creation with business rules
- Product synchronization logic
- End-to-end scenarios covering full flow and failure cases

Run product-service tests:

```
make test-product
```

or:

```
cd product-service && ./vendor/bin/simple-phpunit
```

Run order-service tests:

```
make test-order
```

or:

```
cd order-service && ./vendor/bin/simple-phpunit
```

Run end-to-end tests:

```
make test-e2e
```

or:

```
php tests/e2e/run.php
```

The end-to-end runner starts an isolated Docker Compose project with separate volumes and alternate ports, so it does not modify the main local databases or RabbitMQ state.

The end-to-end tests validate the full product-to-order flow, including:
- successful order creation
- insufficient quantity failure
- missing product failure

## Product API Shape

The `product-service` API uses the same four product fields everywhere:

- `id` as UUID
- `name`
- `price`
- `quantity`

Example:

```
{
  "id": "018f4b0c-8ee8-7d15-bc28-0c65c8e0e9aa",
  "name": "Coffee Mug",
  "price": 12.99,
  "quantity": 100
}
```

## Local Run Without Docker

Each service can run directly on the host machine.

Example:

```
cd product-service
composer install
symfony server:start
```

or:

```
cd product-service
composer install
php -S 127.0.0.1:8000 -t public
```

Do the same for `order-service`.

When running outside Docker, defaults expect:

- PostgreSQL for product-service on `localhost:5433`
- PostgreSQL for order-service on `localhost:5434`
- RabbitMQ on `localhost:5672`

## Security Considerations

Authentication and authorization are intentionally not implemented because they are outside the scope of this task.

In a production system, access control would typically be implemented using:
- JWT-based authentication
- OAuth2 / OpenID Connect
- API Gateway or identity provider

## Known Limitations

- Product updates and deletions are not fully implemented
- Order lifecycle is simplified (single "Processing" state)
- `OrderCompletedMessage` and `OrderRejectedMessage` are not implemented
- No failure handling, compensation logic, or retry orchestration is implemented between services
- No retry or dead-letter queue handling for failed messages
- Eventual consistency is used instead of distributed transactions
