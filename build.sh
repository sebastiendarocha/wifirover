#/bin/bash
# By Olivier FONTÈS <olivier@altsysnet.com>
# This script has the purpose to build a wifirover version
# For every architectures
DPKGDEB="/usr/bin/dpkg-deb";
RSYNC="/usr/bin/rsync -a";
DEST=build_`cat common/etc/version_wr`
VERSION_WR=$(cat common/etc/version_wr)
EXCLUDE_COMMON="--exclude=common/etc/firewall.user"

#Excluding .gitignore files
EXCLUDE='--exclude=.git';
for i in `cat .gitignore`; do
    EXCLUDE="${EXCLUDE} --exclude=$i";
done

# Cleaning tmp files
rm -rf ${DEST}

echo "Generating Debian packages" > /dev/stderr
# Export of Debian tree
mkdir -p ${DEST}/wifirover_debian ${DEST}/wifirover_edgerouter

# Mergin with common directory
$RSYNC $EXCLUDE common/* ${DEST}/wifirover_debian
# Adding OS specific files
$RSYNC $EXCLUDE debian/* ${DEST}/wifirover_debian
# Building debian package
$DPKGDEB --build ${DEST}/wifirover_debian


# Mergin with common directory
$RSYNC $EXCLUDE common/* ${DEST}/wifirover_edgerouter
# Adding OS specific files
$RSYNC $EXCLUDE edgerouter/* ${DEST}/wifirover_edgerouter
# Building EdgeOS package
$DPKGDEB --build ${DEST}/wifirover_edgerouter


rm -rf $DEST/wifirover_debian $DEST/wifirover_edgerouter


echo "Generating LEDE package" > /dev/stderr

# Creating tmp directory
mkdir -p ${DEST}/lede/ipk

# Mergin with common directory
$RSYNC $EXCLUDE $EXCLUDE_COMMON common/* ${DEST}/lede/ipk
# Adding arch specific files
$RSYNC $EXCLUDE openwrt/* ${DEST}/lede/ipk

tar czf $DEST/lede/data.tar.gz -C $DEST/lede/ipk .

cat > $DEST/lede/control << EOF
Package: wifirover
Version: $(cat common/etc/version_wr)
Description: WifiRover Captive portal
Section: extras
Priority: optional
Maintainer: Olivier Fontes <olivier@altsysnet.com>, Sebastien DA ROCHA <sebastien@da-rocha.net>
License: LGPL 2.1
Architecture: all
OE: wifirover
Source: https://github.com/altsysnet/wifirover
Depends: firewall, php7-cgi, dnsmasq-full
EOF

cat > $DEST/lede/conffiles << EOF
/etc/wifi_rover.conf
EOF

tar czf $DEST/lede/control.tar.gz -C $DEST/lede/ control conffiles


echo 2.0 > $DEST/lede/debian-binary

tar czf $DEST/lede/wifirover_${VERSION_WR}.ipk -C $DEST/lede control.tar.gz data.tar.gz  debian-binary > /dev/null

# Append info to Packages
cat $DEST/lede/control >> $DEST/lede/Packages
md5sum -b $DEST/lede/wifirover_${VERSION_WR}.ipk | awk '{ print "MD5Sum: " $1 }' >> $DEST/lede/Packages
wc -c $DEST/lede/wifirover_${VERSION_WR}.ipk | awk '{ print "Size: " $1 }' >> $DEST/lede/Packages
echo "Filename: wifirover_${VERSION_WR}.ipk" >> $DEST/lede/Packages
echo "" >> $DEST/lede/Packages

rm -rf $DEST/lede/control.tar.gz $DEST/lede/data.tar.gz $DEST/lede/debian-binary $DEST/lede/control $DEST/lede/conffiles $DEST/lede/ipk

