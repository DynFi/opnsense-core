#!/bin/sh

if [ -z "$1" ]; then
    echo "USAGE: ./prepare_tgz PORTREVISION"
    exit
fi

V=${1}

F=opnsense-core-20.7.8-$V.tar
FD=opnsense-core-20.7.8-$V.tgz

echo "Preparing $F"
git archive --output=$F HEAD

echo "Building $FD"
gzip -c $F > $FD
rm $F
