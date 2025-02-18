#!/bin/bash

version="1.0.137"
NODE_VERSION=23.7.0

rm -r build_app
mkdir -p build_app/app

cp -r ../../app/ ./build_app/
rm -r ./build_app/app/backend/config/*

# docker run --rm -it -w /app --mount type=bind,source=./build_app/app/backend,target=/app composer update

# docker pull node:$NODE_VERSION
# docker run --rm -it -w /app --mount type=bind,source=./build_app/app/frontend,target=/app node:$NODE_VERSION npm install
# docker image rm node:$NODE_VERSION

docker build --no-cache -f ./Dockerfile -t bidoch78/megaraid-webui:${version} \
    --build-arg megaraid_app_version=${version} .

docker tag bidoch78/megaraid-webui:${version} bidoch78/megaraid-webui:latest

docker push bidoch78/megaraid-webui:${version}
docker push bidoch78/megaraid-webui:latest

docker image rm bidoch78/megaraid-webui:${version}
docker image rm bidoch78/megaraid-webui:latest