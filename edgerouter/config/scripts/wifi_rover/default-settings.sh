#! /bin/vbash
source /opt/vyatta/etc/functions/script-template
configure

delete interfaces bridge br0
set interfaces bridge br0
set interfaces bridge br0 address 192.168.22.1/23

delete interfaces ethernet eth1
set interfaces ethernet eth1 bridge-group bridge br0

delete interfaces ethernet eth2
set interfaces ethernet eth2 bridge-group bridge br0

delete interfaces ethernet eth3
set interfaces ethernet eth3 bridge-group bridge br0

delete interfaces ethernet eth4
set interfaces ethernet eth4 bridge-group bridge br0

delete port-forward
set port-forward hairpin-nat enable
set port-forward lan-interface br0
set port-forward wan-interface eth0

delete service dns forwarding
set service dns forwarding listen-on br0
set service dns forwarding options dhcp-leasefile=/tmp/dhcp.leases
set service dns forwarding options dhcp-range=192.168.22.10,192.168.23.254,2h
set service dns forwarding options dhcp-script=/sbin/dhcp-hooks.sh


set service ssh allow-root
set system time-zone Europe/Paris


delete system flow-accounting
set system flow-accounting netflow version 9
set system flow-accounting netflow enable-egress
set system flow-accounting netflow timeout expiry-interval 60
set system flow-accounting netflow timeout flow-generic 60
set system flow-accounting netflow timeout icmp 60
set system flow-accounting netflow timeout max-active-life 60
set system flow-accounting netflow timeout tcp-fin 10
set system flow-accounting netflow timeout tcp-generic 60
set system flow-accounting netflow timeout tcp-rst 10
set system flow-accounting netflow timeout udp 60
set system flow-accounting interface br0

commit
save

