#/bin/bash
# By Olivier FONTÈS <olivier@altsysnet.com>
# This script has the purpose to build a portal version
# For every architectures
TMP=/tmp;
OWRT_CONFIGDIR=openwrt/etc/config;
OWRT_ETCDIR=openwrt/etc;
OWRT_BACKUPDIR=${TMP}/backup/openwrt.backup;
DEB_PKGDIR=${TMP}/portal.pkg
DPKGDEB="/usr/bin/dpkg-deb";
RSYNC="/usr/bin/rsync -a";
DEST=`cat common/etc/version_wr`

#Excluding .gitignore files
EXCLUDE='--exclude=.git';
for i in `cat .gitignore`; do
    EXCLUDE="${EXCLUDE} --exclude=$i";
done

# Cleaning tmp files
rm -rf ${DEST} ${DEB_PKGDIR} ${TMP}/portal_edgerouter


#Treating specifically config files regarding ARCH

# Creating tmp directory
mkdir -p ${DEST}/ipk

# Mergin with common directory
$RSYNC $EXCLUDE common/* ${DEST}/ipk
# Adding arch specific files
$RSYNC $EXCLUDE openwrt/* ${DEST}/ipk

echo "Generating Debian packages" > /dev/stderr
# Export of Debian tree
mkdir -p ${DEB_PKGDIR} ${TMP}/portal_edgerouter

# Mergin with common directory
$RSYNC $EXCLUDE common/* ${TMP}/portal_edgerouter
# Adding OS specific files
$RSYNC $EXCLUDE edgerouter/* ${TMP}/portal_edgerouter
# Building debian package
$DPKGDEB --build ${TMP}/portal_edgerouter

cp -r /tmp/portal.pkg.deb /tmp/portal_edgerouter.deb $DEST


echo "Generating LEDE package" > /dev/stderr

cat > $DEST/control << EOF 
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

tar czf $DEST/control.tar.gz $DEST/control -C $DEST/

tar czf $DEST/data.tar.gz $DEST/ipk -C $DEST/ipk

echo 2.0 > $DEST/debian-binary

ar r $DEST/wifirover.ipk $DEST/control.tar.gz $DEST/data.tar.gz  $DEST/debian-binary > /dev/null

rm -rf $DEST/control.tar.gz $DEST/data.tar.gz $DEST/debian-binary $DEST/control $DEST/ipk
