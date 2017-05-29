#! /bin/sh

restart=0
chmod_hosts=$(/bin/stat -c '%a' /etc/hosts)
chmod_resolv=$(/bin/stat -c '%a' /etc/resolv.conf)
if [ $chmod_hosts -eq 0 ] ; then
    logger /etc/hosts has wrong mode, restarting dnsmasq 
    restart=1
    /bin/chmod 644 /etc/hosts
fi

if [ $chmod_resolv -eq 0 ] ; then
    logger /etc/resolv.conf has wrong mode, restarting dnsmasq 
    restart=1
    /bin/chmod 644 /etc/resolv.conf
fi

if [ $restart -eq 1 ] ; then
    /etc/init.d/dnsmasq restart

fi
