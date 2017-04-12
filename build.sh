#/bin/bash
# By Olivier FONTÈS <olivier@altsysnet.com>
# This script has the purpose to build a portal version
# For every architectures
OWRT_ARCH="archerc5 tlwdr4300 tlwr1043nd4 tlwr1043nd2 tlwr1043nd wrt160nl unifi locom2 psm2";
OWRT_DIST="barrier attitude 17010" # to remove files/symlinks *.barrier *.attitude
TMP=/tmp;
OWRT_CONFIGDIR=openwrt/etc/config;
OWRT_ETCDIR=openwrt/etc;
OWRT_BACKUPDIR=${TMP}/backup/openwrt.backup;
OWRT_TMPFILESPREFIX=${TMP}/files.;
OWRT_TMPUPDATEPREFIX=${TMP}/update_;
OWRT_TMPUPDATECAREFULLPREFIX=${TMP}/update_carefull_;
LEDE_IMAGEBUILDER_64="lede-imagebuilder-17.01.0-ar71xx-generic.Linux-x86_64.tar.xz";
LEDE_IMAGEBUILDER_DIR_17010=${TMP}/17010/`echo $LEDE_IMAGEBUILDER_64 | sed 's/\.tar.xz//'`;
EXCLUDE_FILES_UPDATE="etc/config/openvpn etc/openvpn"
CAREFULL_FILES_UPDATE="etc/config/network etc/config/wireless etc/config/dhcp etc/firewall.user etc/wifi_rover.conf"
DEB_PKGDIR=${TMP}/portal.pkg
DPKGDEB="/usr/bin/dpkg-deb";
RSYNC="/usr/bin/rsync -a";
TAR="/bin/tar";
DEST=`date +"%Y%m%d"`

function generate_openwrt_lede {
    PROFILE=$1
    MACHINE=$2
    echo "Generating $MACHINE package" > /dev/stderr

    # clean previous generation files
    make clean -C $LEDE_IMAGEBUILDER_DIR_17010
    rm "${OWRT_TMPFILESPREFIX}${MACHINE}"/*

    # removes an error
    mkdir $LEDE_IMAGEBUILDER_DIR_17010/dl

    make image PROFILE="$PROFILE" FILES="${OWRT_TMPFILESPREFIX}${MACHINE}" PACKAGES="iptables-mod-conntrack-extra zoneinfo-core zoneinfo-europe qos-scripts at openvpn-openssl lighttpd lighttpd-mod-cgi lighttpd-mod-rewrite php7-cgi php7-mod-json bash snmpd softflowd lighttpd-mod-auth lighttpd-mod-authn_file wireless-tools -dnsmasq dnsmasq-full ipset tcpdump netdiscover" BIN_DIR="${OWRT_TMPFILESPREFIX}${MACHINE}" -C $LEDE_IMAGEBUILDER_DIR_17010
}

# Download image builders
wget -c https://downloads.lede-project.org/releases/17.01.0/targets/ar71xx/generic/lede-imagebuilder-17.01.0-ar71xx-generic.Linux-x86_64.tar.xz

#Excluding .gitignore files
EXCLUDE='--exclude=.git';
for i in `cat .gitignore`; do
    EXCLUDE="${EXCLUDE} --exclude=$i";
done

#Excluding arch files
for i in ${OWRT_ARCH} ${OWRT_DIST}; do
    EXCLUDE="$EXCLUDE --exclude=*.$i";
done

#Exclude files for update
for i in ${EXCLUDE_FILES_UPDATE} ${CAREFULL_FILES_UPDATE}; do
    EXCLUDE_UPDATE="$EXCLUDE_UPDATE --exclude=*$i";
done


# Cleaning tmp files
for i in ${OWRT_ARCH}; do #openwrt
    rm -rf ${OWRT_TMPFILESPREFIX}$i;
    rm -rf ${OWRT_TMPUPDATEPREFIX}$i;
    rm -rf ${OWRT_TMPUPDATECAREFULLPREFIX}$i;
done
rm -rf $LEDE_IMAGEBUILDER_DIR_17010 ${DEB_PKGDIR} ${TMP}/portal_edgerouter

# Export of openwrt tree;
mkdir -p $OWRT_BACKUPDIR;

#Treating specifically config files regarding ARCH
for i in ${OWRT_ARCH}; do
    for j in `ls ${OWRT_ETCDIR}`; do
        if [ -f ${OWRT_ETCDIR}/$j.$i ]; then
            cp ${OWRT_ETCDIR}/$j ${OWRT_BACKUPDIR}
            cat ${OWRT_ETCDIR}/$j.$i >> ${OWRT_ETCDIR}/$j;
        fi
    done

    for j in `ls ${OWRT_CONFIGDIR}`; do
        if [ -f ${OWRT_CONFIGDIR}/$j.$i ]; then
            cp ${OWRT_CONFIGDIR}/$j ${OWRT_BACKUPDIR}
            cat ${OWRT_CONFIGDIR}/$j.$i >> ${OWRT_CONFIGDIR}/$j;
        fi
    done

    # Creating tmp directory
    mkdir -p ${OWRT_TMPFILESPREFIX}$i

    # Mergin with common directory
    $RSYNC $EXCLUDE common/* ${OWRT_TMPFILESPREFIX}$i
    # Adding arch specific files
    $RSYNC $EXCLUDE openwrt/* ${OWRT_TMPFILESPREFIX}$i

    # Restoring files from backup
    for j in `ls ${OWRT_ETCDIR}`; do
        if [ -f ${OWRT_ETCDIR}/$j.$i ]; then
            cp ${OWRT_BACKUPDIR}/$j ${OWRT_ETCDIR}/$j
        fi
    done
    for j in `ls ${OWRT_CONFIGDIR}`; do
        if [ -f ${OWRT_CONFIGDIR}/$j.$i ]; then
            cp ${OWRT_BACKUPDIR}/$j ${OWRT_CONFIGDIR}/$j
        fi
    done

    # Copy files autorized to update
    mkdir -p ${OWRT_TMPUPDATEPREFIX}$i/
    $RSYNC $EXCLUDE_UPDATE ${OWRT_TMPFILESPREFIX}$i/* ${OWRT_TMPUPDATEPREFIX}$i/

    # Create update tar GZ
    $TAR czf  ${OWRT_TMPUPDATEPREFIX}${i}.tar.gz -C ${OWRT_TMPUPDATEPREFIX}${i}/ .

    CAREFULL_UPDATE=""
    # Copy files to update carefully
    mkdir -p ${OWRT_TMPUPDATECAREFULLPREFIX}$i/
    for file in ${CAREFULL_FILES_UPDATE}; do
        mkdir -p `dirname "${OWRT_TMPUPDATECAREFULLPREFIX}$i/${file}"`
        cp "${OWRT_TMPFILESPREFIX}${i}/${file}" "${OWRT_TMPUPDATECAREFULLPREFIX}$i/${file}.dist-new"
    done

    # TODO Create JSON dictionnary of the files to update carefully
    # Create tar GZ of files to update carefully
    $TAR czf  ${OWRT_TMPUPDATECAREFULLPREFIX}${i}.tar.gz -C ${OWRT_TMPUPDATECAREFULLPREFIX}${i}/ .

done
echo creating $DEST/updates > /dev/stderr
mkdir -p $DEST/updates

echo "Generating Debian packages" > /dev/stderr
# Export of Debian tree
mkdir -p ${DEB_PKGDIR} ${TMP}/portal_edgerouter

# Mergin with common directory
$RSYNC $EXCLUDE common/* ${DEB_PKGDIR}
# Adding OS specific files
$RSYNC $EXCLUDE debian/* ${DEB_PKGDIR}
# Building debian package
$DPKGDEB --build ${DEB_PKGDIR}

# Mergin with common directory
$RSYNC $EXCLUDE common/* ${TMP}/portal_edgerouter
# Adding OS specific files
$RSYNC $EXCLUDE edgerouter/* ${TMP}/portal_edgerouter
# Building debian package
$DPKGDEB --build ${TMP}/portal_edgerouter

cp -r /tmp/portal.pkg.deb /tmp/portal_edgerouter.deb $DEST

# Uncompressingand preparing OpenWrtImageBuilder
mkdir -p  ${TMP}/17010/
$TAR -Jxf $LEDE_IMAGEBUILDER_64 -C ${TMP}/17010/

# Building image for TP-LINK TL-ARCHER C7 AND C5
generate_openwrt_lede archer-c7-v2 archerc5
for src in /tmp/files.archerc5/*-ar71xx-generic-archer-c7-v2-squashfs-factory*.bin ; do
    dest_file=$(echo $src  | sed 's/.*generic-//')
    cp -f $src  $DEST/$dest_file
done
cp -f /tmp/files.archerc5/*-ar71xx-generic-archer-c7-v2-squashfs-sysupgrade.bin $DEST

generate_openwrt_lede archer-c5-v1 archerc5
cp -f /tmp/files.archerc5/*-ar71xx-generic-archer-c5-v1-squashfs-factory.bin $DEST
cp -f /tmp/files.archerc5/*-ar71xx-generic-archer-c5-v1-squashfs-sysupgrade.bin $DEST

cp -f /tmp/update_archerc5.tar.gz /tmp/update_carefull_archerc5.tar.gz $DEST/updates/

# Building image for TP-LINK TL-WRD3600/4300
generate_openwrt_lede tl-wdr4300-v1 tlwdr4300
cp -f /tmp/files.tlwdr4300/*-ar71xx-generic-tl-wdr4300-v1-squashfs-factory.bin $DEST
cp -f /tmp/files.tlwdr4300/*-ar71xx-generic-tl-wdr4300-v1-squashfs-sysupgrade.bin $DEST

generate_openwrt_lede tl-wdr3600-v1 tlwdr4300
cp -f /tmp/files.tlwdr4300/*-ar71xx-generic-tl-wdr3600-v1-squashfs-factory.bin $DEST
cp -f /tmp/files.tlwdr4300/*-ar71xx-generic-tl-wdr3600-v1-squashfs-sysupgrade.bin $DEST

cp -f /tmp/update_tlwdr4300.tar.gz /tmp/update_carefull_tlwdr4300.tar.gz $DEST/updates/

# Building image for TP-LINK TL-WR1043ND 1 to 4
generate_openwrt_lede tl-wr1043nd-v4 tlwr1043nd4
cp -f /tmp/files.tlwr1043nd4//*-ar71xx-generic-tl-wr1043nd-v4-squashfs-factory.bin $DEST
cp -f /tmp/files.tlwr1043nd4/*-ar71xx-generic-tl-wr1043nd-v4-squashfs-sysupgrade.bin $DEST

generate_openwrt_lede tl-wr1043nd-v3 tlwr1043nd2
cp -f /tmp/files.tlwr1043nd2//*-ar71xx-generic-tl-wr1043nd-v3-squashfs-factory.bin $DEST
cp -f /tmp/files.tlwr1043nd2/*-ar71xx-generic-tl-wr1043nd-v3-squashfs-sysupgrade.bin $DEST

generate_openwrt_lede tl-wr1043nd-v2 tlwr1043nd2
cp -f /tmp/files.tlwr1043nd2//*-ar71xx-generic-tl-wr1043nd-v2-squashfs-factory.bin $DEST
cp -f /tmp/files.tlwr1043nd2/*-ar71xx-generic-tl-wr1043nd-v2-squashfs-sysupgrade.bin $DEST

cp -f /tmp/update_tlwr1043nd2.tar.gz /tmp/update_carefull_tlwr1043nd2.tar.gz $DEST/updates/

generate_openwrt_lede tl-wr1043nd-v1 tlwr1043nd
cp -f /tmp/files.tlwr1043nd//*-ar71xx-generic-tl-wr1043nd-v1-squashfs-factory.bin $DEST
cp -f /tmp/files.tlwr1043nd/*-ar71xx-generic-tl-wr1043nd-v1-squashfs-sysupgrade.bin $DEST

cp -f /tmp/update_tlwr1043nd.tar.gz /tmp/update_carefull_tlwr1043nd.tar.gz $DEST/updates/

# Building image for LINKSYS WRT160-NL
#generate_openwrt_lede WRT160NL wrt160nl

# Building image for UBIQUITI Nanostation PicoStationM2
#generate_openwrt_lede ubnt-nano-m-xw psm2

# Building image for UBIQUITI Nanostation Loco
#generate_openwrt_lede ubnt-loco-m-xw locom2

# Building image for UBIQUITI UNIFI
generate_openwrt_lede ubnt-unifi unifi
cp -f /tmp/files.unifi/*-ar71xx-generic-ubnt-unifi-squashfs-factory.bin $DEST
cp -f /tmp/files.unifi/*-ar71xx-generic-ubnt-unifi-squashfs-sysupgrade.bin $DEST

cp -f /tmp/update_unifi.tar.gz /tmp/update_carefull_unifi.tar.gz $DEST/updates/
