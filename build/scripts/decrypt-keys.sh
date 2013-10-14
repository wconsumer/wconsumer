#!/bin/sh

set -e

openssl aes-256-cbc -k "$TEST_KEYS_SECRET" -in integration-tests/keys.php.enc -d -a -out integration-tests/keys.php