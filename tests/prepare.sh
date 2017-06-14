#!/bin/bash

source ./env

start() {
    echo "modify /etc/hosts"
    CMD="sed -i '/127.0.0.2 $HOST_TEST/d' /etc/hosts; echo '127.0.0.2 $HOST_TEST' >> /etc/hosts; /etc/init.d/dnsmasq restart; sleep 5"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}

stop() {
    CMD="sed -i '/127.0.0.2 $HOST_TEST/d' /etc/hosts; /etc/init.d/dnsmasq restart"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}

captiveon() {
    echo "active dns capture"
    CMD="sed -i 's/CAPTURE_DNS=0/CAPTURE_DNS=1/' /etc/wifi_rover.conf; /etc/init.d/wifi_rover restart"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}

captiveoff() {
    CMD="sed -i 's/CAPTURE_DNS=1/CAPTURE_DNS=0/' /etc/wifi_rover.conf; /etc/init.d/wifi_rover restart"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}

portalvalide() {
    echo "active portal"
    sshpass -p $PASSWD scp -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no test_connect.php root@$ROUTEUR:/tmp/
    URL=`sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR "chmod +x /tmp/test_connect.php; /tmp/test_connect.php"`
    curl -s $URL > /dev/null
}

$1


