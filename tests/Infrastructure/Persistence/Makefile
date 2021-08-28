all: up install test down

up:
	docker-compose -p cb -f ./build/docker-compose.yaml up --build -d

install:
	docker-compose -p cb -f ./build/docker-compose.yaml exec app composer install

test:
	docker-compose -p cb -f ./build/docker-compose.yaml exec app ./vendor/bin/phpunit ./tests/Infrastructure/Persistence

down:
	docker-compose -p cb -f ./build/docker-compose.yaml down --rmi all
