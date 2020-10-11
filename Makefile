RUNTIME_TAG='polonaiz/executor'
RUNTIME_DEV_TAG='polonaiz/executor-dev'
REDIS_AUTH_PASS='LOCAL_REDIS_PASS'

build: \
	runtime-build \
	composer-install-in-runtime

update: \
	update-code \
	build

clean: \
	composer-clean

update-code:
	git reset --hard
	git pull

runtime-build:
	docker build \
		--tag ${RUNTIME_TAG} \
		./env/docker/runtime
	docker build \
		--tag ${RUNTIME_DEV_TAG} \
		./env/docker/runtime-dev

runtime-shell:
	docker run --rm -it \
 		${RUNTIME_TAG} bash

runtime-clean:
	docker rmi \
 		${RUNTIME_TAG}

composer-install-in-runtime:
	docker run --rm -it \
		-v $(shell pwd):/opt/project \
		-v ~/.composer:/root/.composer \
 		${RUNTIME_TAG} composer -vvv install -d /opt/project

composer-update-in-runtime:
	docker run --rm -it \
		-v $(shell pwd):/opt/project \
		-v ~/.composer:/root/.composer \
 		${RUNTIME_TAG} composer -vvv update -d /opt/project

composer-clean:
	rm -rf ./vendor

redis-restart: \
	redis-stop \
	redis-start

redis-start:
	docker run \
		--rm --detach --publish 6379:6379 \
		--name redis \
		redis \
		redis-server --requirepass ${REDIS_AUTH_PASS}
	docker ps -f name=redis

redis-stop:
	-docker rm -f redis

worker-start-in-runtime:
	docker run --rm -d --tty --network 'host' \
		-v $(shell pwd):/opt/project \
		--name 'executor-1' \
		${RUNTIME_TAG} /opt/project/bin/worker --worker-id='worker-1' --redis='redis://:${REDIS_AUTH_PASS}:localhost:6379'
#	docker run --rm -d --network 'host' \
#		-v $(shell pwd):/opt/project \
#		--name 'executor-2' \
#		${RUNTIME_TAG} /opt/project/bin/worker --worker-id='worker-2' --redis='redis://:${REDIS_AUTH_PASS}:localhost:6379'

worker-stop-in-runtime:
	docker rm -f worker-1 worker-2

worker-start:
	./bin/worker --worker-id='worker-1' --redis='redis://:${REDIS_AUTH_PASS}@localhost:6379'

stub-start:
	./bin/worker --worker-id='worker-1' --redis='redis://:${REDIS_AUTH_PASS}@localhost:6379'
