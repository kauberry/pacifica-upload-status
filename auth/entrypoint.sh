#!/bin/bash -xe

sed 's/${UPLOAD_STATUS_ADDR}/'${UPLOAD_STATUS_ADDR}'/;'\
's/${UPLOAD_STATUS_PORT}/'${UPLOAD_STATUS_PORT}'/;'\
's/${FILE_REDIRECT_ADDR}/'${FILE_REDIRECT_ADDR}'/;'\
's/${FILE_REDIRECT_PORT}/'${FILE_REDIRECT_PORT}'/;' \
    /etc/nginx/conf.d/mysite.template > /etc/nginx/conf.d/default.conf
nginx -g 'daemon off;'
