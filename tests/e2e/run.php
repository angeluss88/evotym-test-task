<?php

declare(strict_types=1);

final class E2eAssertionFailed extends RuntimeException
{
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

function purgeRabbitMqQueue(): void
{
    exec('docker compose exec -T rabbitmq rabbitmqctl purge_queue messages 2>&1', $output, $exitCode);

    // The queue might not exist yet on a fresh run. That is fine.
    if ($exitCode !== 0 && !str_contains(implode("\n", $output), 'not_found')) {
        throw new E2eAssertionFailed(implode("\n", $output));
    }
}

function runSuccessfulFullFlowScenario(): void
{
    $product = jsonRequest('POST', 'http://localhost:8001/products', [
        'name' => 'E2E Product '.uniqid('', true),
        'price' => 12.99,
        'quantity' => 10,
    ]);

    assertSameValue(201, $product['status'], 'Product creation should succeed.');
    $productId = (string) $product['body']['id'];

    runCommand('make order-consumer-once');

    $order = jsonRequest('POST', 'http://localhost:8002/orders', [
        'productId' => $productId,
        'customerName' => 'John Doe',
        'quantityOrdered' => 3,
    ]);

    assertSameValue(201, $order['status'], 'Order creation should succeed after sync.');
    assertSameValue(3, $order['body']['quantityOrdered'], 'Created order should keep requested quantity.');
    assertSameValue(7, $order['body']['product']['quantity'], 'Remaining product quantity should decrease.');

    $fetchedOrder = jsonRequest('GET', 'http://localhost:8002/orders/'.$order['body']['orderId']);

    assertSameValue(200, $fetchedOrder['status'], 'Fetching created order should succeed.');
    assertSameValue($order['body']['orderId'], $fetchedOrder['body']['orderId'], 'Fetched order id should match.');
}

function runInsufficientQuantityScenario(): void
{
    $product = jsonRequest('POST', 'http://localhost:8001/products', [
        'name' => 'E2E Limited Product '.uniqid('', true),
        'price' => 5.50,
        'quantity' => 2,
    ]);

    assertSameValue(201, $product['status'], 'Second product creation should succeed.');
    $productId = (string) $product['body']['id'];

    runCommand('make order-consumer-once');

    $failedOrder = jsonRequest('POST', 'http://localhost:8002/orders', [
        'productId' => $productId,
        'customerName' => 'John Doe',
        'quantityOrdered' => 5,
    ]);

    assertSameValue(422, $failedOrder['status'], 'Order should fail when requested quantity is too large.');

    $successfulRetry = jsonRequest('POST', 'http://localhost:8002/orders', [
        'productId' => $productId,
        'customerName' => 'John Doe',
        'quantityOrdered' => 2,
    ]);

    assertSameValue(201, $successfulRetry['status'], 'Retry order with valid quantity should succeed.');
    assertSameValue(0, $successfulRetry['body']['product']['quantity'], 'Quantity should only decrease on successful order.');
}

function runMissingProductScenario(): void
{
    $response = jsonRequest('POST', 'http://localhost:8002/orders', [
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
runCommand('docker compose up -d');
purgeRabbitMqQueue();
runSuccessfulFullFlowScenario();
runInsufficientQuantityScenario();
runMissingProductScenario();
echo "E2E tests passed.\n";
