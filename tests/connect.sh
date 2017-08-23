#!/bin/bash

source ./env


modify_timeout() {
    echo "modify CTIMEOUT to $1"
    CMD="sed -i 's/CTIMEOUT=.*/CTIMEOUT=$1/' /etc/wifi_rover.conf; /etc/init.d/wifi_rover restart"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}


$1 $2


