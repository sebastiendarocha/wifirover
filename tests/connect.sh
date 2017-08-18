#!/bin/bash

source ./env


modify() {
    echo "modify CTIMEOUT to $1"
    CMD="sed -i 's/CTIMEOUT=.*/CTIMEOUT=$1/' /etc/wifi_rover.conf; /etc/init.d/wifi_rover restart"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}

disconnect() {
    echo "disconnect client"
    CMD="/sbin/disconnect.sh"
    sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR $CMD
}

portalvalide() {
    echo "active portal"
    sshpass -p $PASSWD scp -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no test_connect.php root@$ROUTEUR:/tmp/
    URL=`sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR "chmod +x /tmp/test_connect.php; /tmp/test_connect.php"`
    curl -s $URL > /dev/null
}

portalvalide_end() {
    echo "active portal with date end parameter"
    sshpass -p $PASSWD scp -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no test_connect_date_end.php root@$ROUTEUR:/tmp/
    URL=`sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR "chmod +x /tmp/test_connect_date_end.php; /tmp/test_connect_date_end.php"`
    curl -s $URL > /dev/null
}

portalvalide_end_weblib() {
    echo "active portal with date end parameter"
    sshpass -p $PASSWD scp -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no test_connect_date_end_weblib.php root@$ROUTEUR:/tmp/
    URL=`sshpass -p $PASSWD ssh -q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@$ROUTEUR "chmod +x /tmp/test_connect_date_end_weblib.php; /tmp/test_connect_date_end_weblib.php"`
    curl -s $URL > /dev/null
}

$1 $2


