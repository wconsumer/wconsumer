#!/bin/bash

set -e

screenshots_dir="./build/screenshots"
screenshots=$(find $screenshots_dir -name "*.png" -type f)

if [ ! -z "$screenshots" ] ; then
  echo "Failed tests screenshots";
  wget http://imgur.com/tools/imgurbash.sh -O ./imgurbash.sh && chmod +x ./imgurbash.sh

  for screenshot in $screenshots; do
    echo -en "${screenshot} "
    ./imgurbash.sh '$screenshot' 2>/dev/null
  done
fi