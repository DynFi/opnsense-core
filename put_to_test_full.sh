#!/bin/bash

CURRENTTAG=$(git rev-parse --abbrev-ref HEAD | sed 's/[^0-9\.]//g')

TARGETIP='192.168.0.111'
SSHPASS='opnsense'

for F in `git diff --name-only ${CURRENTTAG}..${CURRENTTAG}-dff-ui | grep src`; do
    NF=`echo $F | sed 's/src/\/usr\/local/'`
    D=$(echo $NF | sed 's/[^\/]*$//')
    echo "$F >> $NF ($D)"
    sshpass -p "$SSHPASS" ssh root@${TARGETIP} "mkdir -p ${D}"
    sshpass -p "$SSHPASS" scp $F root@${TARGETIP}:${NF}
done
