#!/bin/bash

CONFIG_PATH="$PWD/config.ini"
source ${CONFIG_PATH}

echo "Target host: ${TARGETIP}"
echo "SSH password: ${SSHPASS}"

for F in `git diff --name-only master..suricata | grep -e '^src'`; do
    NF=`echo $F | sed 's/src/\/usr\/local/'`
    D=$(echo $NF | sed 's/[^\/]*$//')
    echo "$F >> $NF ($D)"
    ssh root@10.100.100.1 -p 2808 "mkdir -p ${D}"
    scp -P 2808 $F root@10.100.100.1:${NF}
done
