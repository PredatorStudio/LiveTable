DOCKER = docker compose run --rm php

.PHONY: build install test shell

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