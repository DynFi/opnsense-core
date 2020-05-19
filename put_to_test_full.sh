#!/bin/bash

CONFIG_PATH="$PWD/config.ini"
source ${CONFIG_PATH}

echo "Target host: ${TARGETIP}"
echo "SSH password: ${SSHPASS}"

CURRENTTAG=$(git rev-parse --abbrev-ref HEAD | sed 's/[^0-9\.]//g')

for F in `git diff --name-only ${CURRENTTAG}..${CURRENTTAG}-dff-ui | grep src`; do
    NF=`echo $F | sed 's/src/\/usr\/local/'`
    D=$(echo $NF | sed 's/[^\/]*$//')
    echo "$F >> $NF ($D)"
    sshpass -p "$SSHPASS" ssh root@${TARGETIP} "mkdir -p ${D}"
    sshpass -p "$SSHPASS" scp $F root@${TARGETIP}:${NF}
done
