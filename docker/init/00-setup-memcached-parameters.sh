#!/bin/bash
set -e

# If an external memcached server is configured,
# then the configuration must be updated.

if [ ! -z ${MEMCACHED_HOST} ];
then
    echo `date -u`: Reconfiguring AlphaRPC to use memcached server ${MEMCACHED_HOST}:${MEMCACHED_PORT:-11211}

    sed -i "s/storage_memcached_host: 127.0.0.1/storage_memcached_host: ${MEMCACHED_HOST}/g" /home/alpharpc/alpharpc/app/config/alpharpc_config.yml

    if [ ! -z ${MEMCACHED_PORT} ];
    then
        sed -i "s/storage_memcached_port: 11211/storage_memcached_port: ${MEMCACHED_PORT}/g" /home/alpharpc/alpharpc/app/config/alpharpc_config.yml
    fi

    # Make sure that the local memcached service is not started
    touch /etc/service/memcached/down
fi
