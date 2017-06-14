#!/bin/bash

/root/detect_docker_config.php > /etc/interfaces.conf

/etc/init.d/wifi_rover start

if [ "$WIFICMD" == 'test' ]; then
    phpunit  /root/tests/
else
    tail -f /tmp/wr.log /var/log/lighttpd/error.log
fi
