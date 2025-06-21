#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

STACK_ENV_FILE=$DIR/.env
APP_ENV_FILE=$DIR/../.env

if [ ! -f "$STACK_ENV_FILE" ]; then
    echo "File $STACK_ENV_FILE does not exist. Please create it from the template and fill in the variables."
    exit 1
fi

if [ ! -f "$APP_ENV_FILE" ]; then
    echo "File $APP_ENV_FILE does not exist. Please create it from the template and fill in the variables."
    exit 1
fi

# export all variables from .env file
set -a
source $DIR/.env
source $DIR/../.env


########################################################################################################################
# Build image if it does not exist
########################################################################################################################
echo "Checking if image ${STACK_NAME}_php exists..."
if [ "$(docker images -q ${STACK_NAME}_php 2> /dev/null)" = "" ]; then
    echo "Image ${STACK_NAME}_php does not exist. Building it..."
    $DIR/build-image.sh
fi

########################################################################################################################
# Deploy the stack
########################################################################################################################
echo "Deploying stack..."
$DIR/deploy-stack.sh

# wait php container is up
echo "Waiting for php container to start..."
PHP_CONTAINER=$(docker ps --filter name="${STACK_NAME}_stack_php\." --format "{{.Names}}")
while [ -z "$PHP_CONTAINER" ]; do
    sleep 1
    PHP_CONTAINER=$(docker ps --filter name="${STACK_NAME}_stack_php\." --format "{{.Names}}")
done
echo "API container is up!"

########################################################################################################################
# Setup stacks
########################################################################################################################
echo "Setting up stack..."
echo "API container: $PHP_CONTAINER"
echo "Installing composer dependencies..."
docker exec -it -w /app $PHP_CONTAINER sh -c "XDEBUG_MODE=off composer install"

########################################################################################################################
# Show info
########################################################################################################################
echo
echo "$STACK_NAME is configured and up!"
echo
echo "API is available at ${APP_URL}"
echo
