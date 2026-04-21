.PHONY: order-consumer order-consumer-once test-product test-order test-e2e

order-consumer:
	docker compose exec -T -e APP_DEBUG=0 order-service php bin/console messenger:consume async

order-consumer-once:
	docker compose exec -T -e APP_DEBUG=0 order-service php bin/console messenger:consume async --limit=1 --time-limit=10

test-product:
	cd product-service && ./vendor/bin/simple-phpunit

test-order:
	cd order-service && ./vendor/bin/simple-phpunit

test-e2e:
	php tests/e2e/run.php
