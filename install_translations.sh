#!/bin/bash

CONFIG_PATH="$PWD/config.ini"
source ${CONFIG_PATH}

echo "Target host: ${TARGETIP}"
echo "SSH password: ${SSHPASS}"

sshpass -p "$SSHPASS" scp -r src/share/locale $F root@${TARGETIP}:/usr/local/share
