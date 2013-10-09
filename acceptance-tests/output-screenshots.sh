#!/bin/sh

set -e

screenshots_dir="./build/screenshots"
screenshots=$(find $screenshots_dir -name "*.png" -type f)

if [ -n $screenshots ] ; then
  echo "Below are screenshots of failed tests in base64 encoding:";
  for f in $screenshots; do
    echo "\n\n\n";
    echo $f;
    base64 $f;
  done
fi