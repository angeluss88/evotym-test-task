.PHONY: order-consumer

order-consumer:
	docker compose exec -T -e APP_DEBUG=0 order-service php bin/console messenger:consume async
