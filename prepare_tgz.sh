#!/bin/sh

if [ -z "$1" ]; then
    echo "USAGE: ./prepare_tgz PORTREVISION"
    exit
fi

V=${1}

F=opnsense-core-19.7.10-$V.tar
FD=opnsense-core-19.7.10-$V.tgz

echo "Preparing $F"
git archive --output=$F HEAD
tar --delete --file=$F build_css.sh
tar --delete --file=$F build_version_core.sh
tar --delete --file=$F fetch_core_updates.sh
tar --delete --file=$F list_pkg_files.sh
tar --delete --file=$F list_plist_files.sh
tar --delete --file=$F prepare_patch.sh
tar --delete --file=$F prepare_tgz.sh
tar --delete --file=$F put_to_test.sh
tar --delete --file=$F put_to_test_full.sh
tar --delete --file=$F config.ini.example
tar --delete --file=$F install_translations.sh
tar --delete --file=$F transl

echo "Building $FD"
gzip -c $F > $FD
rm $F

