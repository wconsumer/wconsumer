#!/bin/sh

# We need to encrypt keys used for testing in a way suitable for Travis. The first option here is
# to use Travis "Secure Environment Variables" feature which allows encrypting env var with asymmetric algo
# using public key for encryption on ours end. Then Travis CI server is able to decrypt this value using
# its private key part on its end. However, only very short strings can be encrypted with assymetric algo (less than 128
# bytes length). All relatively long strings should be encoded with symmetric algo using a shared secret. So
# there is a second options we have. Generate random password (shared secret) and pass it to Travis using
# asymmetric encryption and "Secure Environment Variables" Travis feature. This password is used for symmetric
# encryption of the keys file which encrypted version is placed in repository.

set -ve

# Generate password for symmetric encryption
password=`cat /dev/urandom | head -c 10000 | openssl sha1`

# Encrypt keys file with password
openssl aes-256-cbc -k "$password" -in keys.php -out keys.php.enc -a

# Encrypt password with asymmetric encryption and update it in .travis.yml
travis encrypt TEST_KEYS_SECRET="$password" --add env.global --override