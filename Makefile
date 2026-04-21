.PHONY: product-consumer order-consumer order-consumer-once product-consumer-once test-product test-order test-e2e

product-consumer:
	docker compose exec -T -e APP_DEBUG=0 product-service php bin/console messenger:consume order_created

order-consumer:
	docker compose exec -T -e APP_DEBUG=0 order-service php bin/console messenger:consume product_sync

product-consumer-once:
	docker compose exec -T -e APP_DEBUG=0 product-service php bin/console messenger:consume order_created --limit=1 --time-limit=10

order-consumer-once:
	docker compose exec -T -e APP_DEBUG=0 order-service php bin/console messenger:consume product_sync --limit=1 --time-limit=10

test-product:
	cd product-service && ./vendor/bin/simple-phpunit

test-order:
	cd order-service && ./vendor/bin/simple-phpunit

test-e2e:
	php tests/e2e/run.php
