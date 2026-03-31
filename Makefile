DOCKER = docker compose run --rm php
SERVE_ENV = DB_CONNECTION=sqlite DB_DATABASE=/app/workbench/database/database.sqlite

.PHONY: build install test shell serve demo-build demo-seed

build:
	docker compose build

install: build
	$(DOCKER) composer install

test:
	$(DOCKER) ./vendor/bin/pest

test-coverage:
	$(DOCKER) ./vendor/bin/pest --coverage --min=100

test-filter:
	$(DOCKER) ./vendor/bin/pest --filter="$(F)"

shell:
	$(DOCKER) bash

demo-build:
	$(DOCKER) bash -c "$(SERVE_ENV) php vendor/bin/testbench workbench:build"
	$(DOCKER) bash -c "$(SERVE_ENV) php vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DatabaseSeeder'"
	$(DOCKER) bash -c "rm -rf /app/vendor/orchestra/testbench-core/laravel/resources/views/vendor/live-table && rm -f /app/vendor/orchestra/testbench-core/laravel/storage/framework/views/*.php"

demo-link:
	docker compose exec serve bash -c "rm -rf /app/vendor/orchestra/testbench-core/laravel/resources/views/vendor/live-table && rm -f /app/vendor/orchestra/testbench-core/laravel/storage/framework/views/*.php && echo 'OK'"

serve:
	docker compose up serve

optimize:
	docker compose exec serve php vendor/bin/testbench cache:clear
	docker compose exec serve php vendor/bin/testbench config:clear
	docker compose exec serve php vendor/bin/testbench route:clear
	docker compose exec serve php vendor/bin/testbench view:clear
	docker compose exec serve php vendor/bin/testbench optimize
