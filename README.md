# wifirover
Captive portal project

# Debian build
The following packages are required:
* bzip2
* git
* make
* libncurses-dev
* zlib1g-dev


# LEDE/OpenWRT packages required:
* iptables-mod-conntrack-extra
* zoneinfo-core
* zoneinfo-europe
* lighttpd
* lighttpd-mod-cgi
* lighttpd-mod-rewrite
* lighttpd-mod-auth
* lighttpd-mod-authn\_file
* php7-cgi
* php7-mod-json
* bash
* dnsmasq or dnsmasq-full (for whitelist domains)
* wireless-tools

# Running unit tests on docker

## Prepare

You need docker and docker-compose

You have to create 2 networks:

```
docker network create --subnet 192.168.22.0/24 lan
docker network create --subnet 192.168.32.0/24 corporate
```

## Running test

```
docker-compose up --build wifirover
```
