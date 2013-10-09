#!/bin/bash

set -e

screenshots_dir="./build/screenshots"
screenshots=$(find $screenshots_dir -name "*.png" -type f)

if [ ! -z $screenshots ] ; then
  echo "Below are screenshots of failed tests in base64 encoding:";
  for f in $screenshots; do
    echo -e "\n\n\n";
    echo $f;
    base64 $f;
  done
fi