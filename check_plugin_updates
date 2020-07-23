#!/bin/bash

CONFIG_PATH="$PWD/config.ini"
source ${CONFIG_PATH}

PREVTAG="19.7.10"

if [ -n "$1" ]; then
    PREVTAG="$1"
fi

CURRENTTAG=$(git rev-parse --abbrev-ref HEAD | sed 's/[^0-9\.]//g')

echo "$PREVTAG..$CURRENTTAG"

cd ${PLUGINSDIR}
UPDATES=$(git diff --name-only ${PREVTAG}..${CURRENTTAG} | sed 's/\/[^\/]*//2g' | uniq)
cd ${COREDIR}

for U in $UPDATES; do
  cat plugins | grep $U
done
