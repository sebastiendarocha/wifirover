#! /bin/sh

dhcp_listen=$(/bin/netstat -nupa | grep dnsmasq | grep -c ':67 ')
if [ $dhcp_listen -eq 0 ] ; then
    logger watchdog.sh dnsmasq not listening to dhcp, restarting
    /etc/init.d/dnsmasq restart
fi


dns_listen=$(/bin/netstat -nupa | grep dnsmasq | grep -c ':53 ')
if [ $dns_listen -eq 0 ] ; then
    logger watchdog.sh dnsmasq not listening to dns, restarting
    /etc/init.d/dnsmasq restart
fi

