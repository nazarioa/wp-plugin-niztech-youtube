#!/bin/bash

#Remove the contents of previous distributions
rm -rf ./dist
mkdir ./dist

#Remove old build files
rm -rf ./build/niztech-youtube/
rm -rf ./build/vendor/

# Install dependencies for NiztechYouTube project. Dependencies are not scoped at this point
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Copies the source code and dependencies into the build directory in preparation for scoping
rsync -a \
  --exclude='.editorconfig*' \
  --exclude='.git*' \
  --exclude='build' \
  --exclude='dist' \
  --exclude='build.sh' \
  --exclude='composer.*' \
  --exclude='node_modules' \
  --exclude='tests' \
  ./ build/niztech-youtube/


cd ./build || exit

# We want to use the path (./niztech-youtube/vendor) for the final home...
# therefor, move the vendor folder over to free up the path.

mv ./niztech-youtube/vendor ./niztech-youtube-vendor-pre-process

# Install dependencies needed by php-scoper
composer install

# Run PHPScoper (adds a prefix to namespaces of dependencies) so that we avoid collision
./vendor/humbug/php-scoper/bin/php-scoper add-prefix --config ./scoper.inc.php

timestamp=$(date +%s)
zip -rq ../dist/niztech-youtube-"$timestamp".zip niztech-youtube/
