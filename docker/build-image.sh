#!/usr/bin/env bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

STACK_ENV_FILE=$DIR/.env
APP_ENV_FILE=$DIR/../.env

if [ ! -f "$STACK_ENV_FILE" ]; then
    echo "File $STACK_ENV_FILE does not exist. Please create it from the template."
    exit 1
fi

if [ ! -f "$APP_ENV_FILE" ]; then
    echo "File $APP_ENV_FILE does not exist. Please create it from the template."
    exit 1
fi

# export all variables from .env file
set -a
source $DIR/.env
source $DIR/../.env

# check if STACK_NAME variable is set
if [ -z ${STACK_NAME+x} ]; then
    echo "STACK_NAME is unset. Please set it in $STACK_ENV_FILE"
    exit 1
fi

docker build -t "${STACK_NAME}_php" -f $DIR/php-fpm/Dockerfile .