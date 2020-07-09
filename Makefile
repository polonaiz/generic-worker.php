RUNTIME_TAG='polonaiz/executor'

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
		./env/docker

runtime-bash:
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
