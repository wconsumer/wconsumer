#!/bin/bash

set -e

screenshots_dir="./build/screenshots"
screenshots=$(find $screenshots_dir -name "*.png" -type f)

if [ ! -z "$screenshots" ] ; then
  #wget --quiet "http://stedolan.github.io/jq/download/linux32/jq"
  #chmod +x ./jq
  echo "Failed tests screenshots";
  for screenshot in $screenshots; do
    echo -en "${screenshot} "
    response=`curl -sS --header "Authorization: Client-Id 18fdc66f59434c9" -F "image=@$screenshot" https://api.imgur.com/3/image`
    #link=`echo "$response" | ./jq '.data.link'`
    if [ -z "$link" ] || [ "$link" = "null" ] ; then
      echo "$response"
    else
      echo "$link"
    fi
  done
fi