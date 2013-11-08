#!/bin/sh

set -e

openssl aes-256-cbc -k "$TEST_KEYS_SECRET" -in tests/Integration/keys.php.enc -d -a -out tests/Integration/keys.php