#!/bin/sh

set -e

fixturePort=80
serverUrl='http://127.0.0.1:4444'
serverVersion='2.35.0'
serverFile=selenium-server-standalone-$serverVersion.jar

echo "Starting Python web server"
sudo python -m SimpleHTTPServer $fixturePort &

echo "Starting xvfb"
echo "Starting Selenium"
if [ ! -f $serverFile ]; then
    wget http://selenium.googlecode.com/files/selenium-server-standalone-$serverVersion.jar -O $serverFile
fi
xvfb-run java -jar $serverFile > /dev/null &

wget --retry-connrefused --tries=60 --waitretry=1 --output-file=/dev/null $serverUrl/wd/hub/status -O /dev/null
if [ ! $? -eq 0 ]; then
    echo "Selenium Server not started";
    exit 255;
else
    echo "Finished setup"
fi
