#!/bin/bash -xe

# basic syntax check against all php files
./vendor/bin/phpcs -n --extensions=php --ignore=*/websystem/*,*/third_party/*,*/system/*,*/migrations/*,*/libraries/*,*/logs/* --standard=pacifica_php_ruleset.xml application/
if [[ $CODECLIMATE_REPO_TOKEN ]]; then
phpunit --coverage-clover build/logs/clover.xml tests
./vendor/bin/test-reporter
fi
