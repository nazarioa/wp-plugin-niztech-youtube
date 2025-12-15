#!/bin/bash
set +x

echo "1 of 7) Removing old files";
#Remove the contents of previous distributions
rm -rf ./dist/niztech-youtube/
mkdir -p ./dist

#Remove old build files
rm -rf ./build/niztech-youtube--before-prefix/

echo "2 of 7) Installing dependencies";
# Install prod dependencies for NiztechYouTube project. Dependencies are not scoped at this point
composer install --no-dev --optimize-autoloader --classmap-authoritative --quiet

echo "3 of 7) Creating build/niztech-youtube";
# Copies the source code and dependencies into the build directory in preparation for scoping
rsync -a \
  --exclude='.editorconfig*' \
  --exclude='.git*' \
  --exclude='.idea' \
  --exclude='build' \
  --exclude='build.sh' \
  --exclude='composer.*' \
  --exclude='dist' \
  --exclude='node_modules' \
  --exclude='tests' \
  ./ ./build/niztech-youtube--before-prefix/


cd ./php-scoper-build-tool || exit

echo "4 of 7) Installing php-scoper";
# Install dependencies needed by php-scoper
composer install --quiet

echo "5 of 7) Running php-scoper";
# Run PHPScoper (adds a prefix to namespaces of dependencies) so that we avoid collision
./vendor/humbug/php-scoper/bin/php-scoper add-prefix -s -q --config ./scoper.inc.php

echo "6 of 7) Zipping";
cd ../dist || exit
#bundle it up
timestamp=$(date +%s)
zip -rq ./niztech-youtube-"$timestamp".zip ./niztech-youtube

echo "7 of 7) Cleanup";
#remove build version of "niztech-youtube" folder
rm -rf ./niztech-youtube/

echo "Done!";
