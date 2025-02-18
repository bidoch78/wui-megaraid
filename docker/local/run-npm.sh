#!/bin/bash
NODE_VERSION=23.7.0
docker pull node:$NODE_VERSION
docker run --rm -it -w /app --mount type=bind,source=./../../app/frontend,target=/app node:$NODE_VERSION npm $@