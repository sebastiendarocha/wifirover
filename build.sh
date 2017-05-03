#/bin/bash
# By Olivier FONTÈS <olivier@altsysnet.com>
# This script has the purpose to build a wifirover version
# For every architectures
DPKGDEB="/usr/bin/dpkg-deb";
RSYNC="/usr/bin/rsync -a";
DEST=build_`cat common/etc/version_wr`

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


echo "Generating LEDE package" > /dev/stderr

# Creating tmp directory
mkdir -p ${DEST}/lede/ipk

# Mergin with common directory
$RSYNC $EXCLUDE common/* ${DEST}/lede/ipk
# Adding arch specific files
$RSYNC $EXCLUDE openwrt/* ${DEST}/lede/ipk

tar czf $DEST/lede/data.tar.gz $DEST/lede/ipk -C $DEST/lede/ipk

cat > $DEST/lede/control << EOF
Package: wifirover
Version: $(cat common/etc/version_wr)
Description: WifiRover Captive portal
Section: extras
Priority: optional
Maintainer: Olivier Fontes <olivier@altsysnet.com>, Sebastien DA ROCHA <sebastien@altsysnet.com>
License: LGPL 2.1
Architecture: all
OE: wifirover
EOF

tar czf $DEST/lede/control.tar.gz -C $DEST/lede/ control 


echo 2.0 > $DEST/lede/debian-binary

ar r $DEST/lede/wifirover.ipk $DEST/lede/control.tar.gz $DEST/lede/data.tar.gz  $DEST/lede/debian-binary > /dev/null



rm -rf $DEST/lede/control.tar.gz $DEST/lede/data.tar.gz $DEST/lede/debian-binary $DEST/lede/control $DEST/lede/ipk
