#!/bin/bash

/root/detect_docker_config.php > /etc/interfaces.conf


if [ "$WIFICMD" == 'test' ]; then
    phpunit  /root/tests/
else
    /etc/init.d/wifi_rover start
    tail -f /tmp/wr.log /var/log/lighttpd/error.log
fi
