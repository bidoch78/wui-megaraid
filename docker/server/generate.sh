#!/bin/bash

version="1.0.136"

rm -r build_app
mkdir -p build_app/app

cp -r ../../app/ ./build_app/
rm -r ./build_app/app/backend/config/*

docker build --no-cache -f ./Dockerfile -t bidoch78/megaraid-webui:${version} \
    --build-arg megaraid_app_version=${version} .

docker tag bidoch78/megaraid-webui:${version} bidoch78/megaraid-webui:latest

docker push bidoch78/megaraid-webui:${version}
docker push bidoch78/megaraid-webui:latest

docker image rm bidoch78/megaraid-webui:${version}
docker image rm bidoch78/megaraid-webui:latest