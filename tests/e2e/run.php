<?php

declare(strict_types=1);

final class E2eAssertionFailed extends RuntimeException
{
}

const E2E_PROJECT_NAME = 'symfony-microservices-task-e2e';
const E2E_PRODUCT_SERVICE_PORT = 18001;
const E2E_ORDER_SERVICE_PORT = 18002;
const E2E_RABBITMQ_PORT = 25672;
const E2E_RABBITMQ_MANAGEMENT_PORT = 25673;
const E2E_PRODUCT_DB_PORT = 15433;
const E2E_ORDER_DB_PORT = 15434;

function composeBaseCommand(): string
{
    return sprintf(
        'PRODUCT_SERVICE_PORT=%d ORDER_SERVICE_PORT=%d RABBITMQ_PORT=%d RABBITMQ_MANAGEMENT_PORT=%d PRODUCT_DB_PORT=%d ORDER_DB_PORT=%d docker compose -p %s',
        E2E_PRODUCT_SERVICE_PORT,
        E2E_ORDER_SERVICE_PORT,
        E2E_RABBITMQ_PORT,
        E2E_RABBITMQ_MANAGEMENT_PORT,
        E2E_PRODUCT_DB_PORT,
        E2E_ORDER_DB_PORT,
        E2E_PROJECT_NAME,
    );
}

function productServiceUrl(string $path): string
{
    return 'http://localhost:'.E2E_PRODUCT_SERVICE_PORT.$path;
}

function orderServiceUrl(string $path): string
{
    return 'http://localhost:'.E2E_ORDER_SERVICE_PORT.$path;
}

/**
 * @param array<string, mixed>|null $payload
 *
 * @return array{status: int, body: array<string, mixed>}
 */
function jsonRequest(string $method, string $url, ?array $payload = null): array
{
    $handle = curl_init($url);

    if ($handle === false) {
        throw new E2eAssertionFailed(sprintf('Unable to initialize curl for "%s".', $url));
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);

    if ($payload !== null) {
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    $body = curl_exec($handle);
    $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

    if ($body === false) {
        $error = curl_error($handle);
        curl_close($handle);

        throw new E2eAssertionFailed(sprintf('HTTP request to "%s" failed: %s', $url, $error));
    }

    curl_close($handle);

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

    return [
        'status' => $statusCode,
        'body' => $decoded,
    ];
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new E2eAssertionFailed(sprintf(
            '%s Expected %s, got %s.',
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new E2eAssertionFailed($message);
    }
}

function runCommand(string $command): string
{
    $output = [];
    $exitCode = 0;

    exec($command.' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        throw new E2eAssertionFailed(sprintf(
            "Command failed: %s\n%s",
            $command,
            implode("\n", $output),
        ));
    }

    return implode("\n", $output);
}

function waitUntil(callable $callback, int $timeoutSeconds, string $timeoutMessage): void
{
    $deadline = time() + $timeoutSeconds;

    while (time() <= $deadline) {
        if ($callback() === true) {
            return;
        }

        usleep(500000);
    }

    throw new E2eAssertionFailed($timeoutMessage);
}

function purgeRabbitMqQueue(): void
{
    exec(composeBaseCommand().' exec -T rabbitmq rabbitmqctl purge_queue messages 2>&1', $output, $exitCode);

    // The queue might not exist yet on a fresh run. That is fine.
    if ($exitCode !== 0 && !str_contains(implode("\n", $output), 'not_found')) {
        throw new E2eAssertionFailed(implode("\n", $output));
    }
}

function waitForHttpServices(): void
{
    waitUntil(
        static function (): bool {
            try {
                return jsonRequest('GET', productServiceUrl('/products'))['status'] === 200;
            } catch (Throwable) {
                return false;
            }
        },
        60,
        'Product service did not become ready in time.',
    );

    waitUntil(
        static function (): bool {
            try {
                return jsonRequest('GET', orderServiceUrl('/orders'))['status'] === 200;
            } catch (Throwable) {
                return false;
            }
        },
        60,
        'Order service did not become ready in time.',
    );
}

function waitForProductSync(string $productId): void
{
    waitUntil(
        static function () use ($productId): bool {
            $command = sprintf(
                "%s exec -T order-db psql -U symfony -d order_service -t -A -c %s",
                composeBaseCommand(),
                escapeshellarg(sprintf("SELECT COUNT(*) FROM products WHERE id = '%s';", $productId)),
            );

            return trim(runCommand($command)) === '1';
        },
        30,
        sprintf('Product "%s" was not synchronized to order-service in time.', $productId),
    );
}

function fetchOrderServiceProductQuantity(string $productId): int
{
    $command = sprintf(
        "%s exec -T order-db psql -U symfony -d order_service -t -A -c %s",
        composeBaseCommand(),
        escapeshellarg(sprintf("SELECT quantity FROM products WHERE id = '%s';", $productId)),
    );

    return (int) trim(runCommand($command));
}

function runSuccessfulFullFlowScenario(): void
{
    $product = jsonRequest('POST', productServiceUrl('/products'), [
        'name' => 'E2E Product '.uniqid('', true),
        'price' => 12.99,
        'quantity' => 10,
    ]);

    assertSameValue(201, $product['status'], 'Product creation should succeed.');
    $productId = (string) $product['body']['id'];

    waitForProductSync($productId);

    $order = jsonRequest('POST', orderServiceUrl('/orders'), [
        'productId' => $productId,
        'customerName' => 'John Doe',
        'quantityOrdered' => 3,
    ]);

    assertSameValue(201, $order['status'], 'Order creation should succeed after sync.');
    assertSameValue(3, $order['body']['quantityOrdered'], 'Created order should keep requested quantity.');
    assertSameValue(7, $order['body']['product']['quantity'], 'Remaining product quantity should decrease.');

    $fetchedOrder = jsonRequest('GET', orderServiceUrl('/orders/'.$order['body']['orderId']));

    assertSameValue(200, $fetchedOrder['status'], 'Fetching created order should succeed.');
    assertSameValue($order['body']['orderId'], $fetchedOrder['body']['orderId'], 'Fetched order id should match.');
}

function runInsufficientQuantityScenario(): void
{
    $product = jsonRequest('POST', productServiceUrl('/products'), [
        'name' => 'E2E Limited Product '.uniqid('', true),
        'price' => 5.50,
        'quantity' => 2,
    ]);

    assertSameValue(201, $product['status'], 'Second product creation should succeed.');
    $productId = (string) $product['body']['id'];

    waitForProductSync($productId);

    $failedOrder = jsonRequest('POST', orderServiceUrl('/orders'), [
        'productId' => $productId,
        'customerName' => 'John Doe',
        'quantityOrdered' => 5,
    ]);

    assertSameValue(422, $failedOrder['status'], 'Order should fail when requested quantity is too large.');
    assertSameValue(2, fetchOrderServiceProductQuantity($productId), 'Failed order must not change quantity.');

    $successfulRetry = jsonRequest('POST', orderServiceUrl('/orders'), [
        'productId' => $productId,
        'customerName' => 'John Doe',
        'quantityOrdered' => 2,
    ]);

    assertSameValue(201, $successfulRetry['status'], 'Retry order with valid quantity should succeed.');
    assertSameValue(0, $successfulRetry['body']['product']['quantity'], 'Quantity should only decrease on successful order.');
}

function runMissingProductScenario(): void
{
    $response = jsonRequest('POST', orderServiceUrl('/orders'), [
        'productId' => '00000000-0000-4000-8000-000000000001',
        'customerName' => 'John Doe',
        'quantityOrdered' => 1,
    ]);

    assertSameValue(404, $response['status'], 'Order for missing product should return 404.');
    assertTrueValue(
        str_contains((string) $response['body']['message'], 'was not found'),
        'Missing product error message should mention not found.',
    );
}

echo "Running end-to-end tests...\n";

try {
    runCommand(composeBaseCommand().' down -v --remove-orphans');
    runCommand(composeBaseCommand().' up -d --build');
    waitForHttpServices();
    purgeRabbitMqQueue();
    runSuccessfulFullFlowScenario();
    runInsufficientQuantityScenario();
    runMissingProductScenario();
    echo "E2E tests passed.\n";
} finally {
    runCommand(composeBaseCommand().' down -v --remove-orphans');
}
