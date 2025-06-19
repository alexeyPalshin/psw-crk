#!/usr/bin/env bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# export all variables from .env file
set -a
source $DIR/.env

docker stack rm "${STACK_NAME}_stack"

echo "Waiting until network is removed..."
while docker network ls | grep "${STACK_NAME}_stack_app" > /dev/null; do
    sleep 1
done

if [[ $1 == "--with-volumes" ]]; then
  echo "Removing volumes..."
  for volume in $(docker volume ls -q | grep "${STACK_NAME}_stack") ; do
      if [ "$(docker inspect $volume --format '{{ index .Labels "com.docker.stack.namespace" }}' &2>/dev/null)" == "${STACK_NAME}_stack" ] ; then
        docker volume rm $volume ;
      fi ;
    done

fi

echo "Done."

