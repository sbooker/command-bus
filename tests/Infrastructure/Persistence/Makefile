dc := docker compose -p cb -f ./build/docker-compose.yaml
dcup := $(dc) up --build -d

all:
	up-db
	up-php VER=8.3
	update
	test
	down

up-db:
	$(dcup) mysql8 && \
	$(dcup) pgsql12
up-php:
	$(dc) build --build-arg PHP_VER=$(VER) && \
	$(dc) up -d app
update:
	$(dc) exec app composer update
test:
	$(dc) exec app ./vendor/bin/phpunit ./tests/Infrastructure/Persistence
down:
	$(dc) down
down-all:
	$(dc) down --rmi all
