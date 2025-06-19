#!/usr/bin/env bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# export all variables from .env files
set -a
source $DIR/.env
source $DIR/../.env

docker stack deploy -c $DIR/stack.yml "${STACK_NAME}_stack"
