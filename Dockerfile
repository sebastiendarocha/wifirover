# Portal WifiRover
# Version 0.1.0

FROM debian:jessie

MAINTAINER "Sébastien DA ROCHA" <sebastien@altsysnet.com>

ENV DEBIAN_FRONTEND noninteractive
RUN (apt-get update && apt-get upgrade -y -q && apt-get -y -q autoremove)

RUN apt update -y ; apt-get install -y lighttpd php5-cgi dnsmasq vim sudo dnsutils openvpn iptables ipset net-tools
RUN apt-get install -y phpunit

COPY common/ /
COPY debian/ /
COPY tests/data/ /
RUN touch /etc/default/fprobe

CMD /root/entrypoint.sh
