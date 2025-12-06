#!/bin/bash

rm -rf ./build
mkdir ./build

composer install --no-dev --optimize-autoloader --classmap-authoritative

rsync -a \
  --exclude='build.sh' \
  --exclude='composer.*' \
  --exclude='node_modules' \
  --exclude='.git*' \
  --exclude='.editorconfig*' \
  --exclude='tests' \
  --exclude='build' \
  ./ build/niztech-youtube/

cd build
zip -rq niztech-youtube.zip niztech-youtube/
