.PHONY: up down restart logs ps composer-install shell test test-unit test-integration analyse fix

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose down && docker compose up -d

logs:
	docker compose logs -f

ps:
	docker compose ps

composer-install:
	docker compose run --rm php composer install

shell:
	docker compose exec php bash

test:
	docker compose exec php composer test

test-unit:
	docker compose exec php composer test -- --testsuite Unit

test-integration:
	docker compose exec php composer test -- --testsuite Integration

analyse:
	docker compose exec php vendor/bin/phpstan analyse --level=8

fix:
	docker compose exec php vendor/bin/php-cs-fixer fix
