#!/bin/bash -xe

sed 's/${PROXY_ADDR}/'${PROXY_ADDR}'/;'\
's/${PROXY_PORT}/'${PROXY_PORT}'/;'\
's/${FILE_REDIRECT_ADDR}/'${FILE_REDIRECT_ADDR}'/;'\
's/${FILE_REDIRECT_PORT}/'${FILE_REDIRECT_PORT}'/;' \
    /etc/nginx/conf.d/mysite.template > /etc/nginx/conf.d/default.conf
nginx -g 'daemon off;'
