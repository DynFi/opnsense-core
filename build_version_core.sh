#!/bin/sh

TARGETIP='192.168.0.114'
SSHPASS='dynfi'

cp -f src/opnsense/version/core.in src/opnsense/version/core

sed -i -e "s/%%CORE\_ARCH%%/amd64/g" src/opnsense/version/core
sed -i -e "s/%%CORE\_FLAVOUR%%/OpenSSL/g" src/opnsense/version/core

for PNAME in $(cat src/opnsense/version/core | awk '{ print $2 }' | sed 's/[^a-zA-Z_]*//g' | grep -v "^$"); do
  V=$(cat Makefile | grep "$PNAME?=" | grep -v "{" | awk 'BEGIN{FS="="} { print $2 }' | sed 's/^[ \t]*//;s/[ \t]*$//')
  echo "$PNAME=$V"
  EV=$(echo "$V" | sed 's/\//\\\//g')
  sed -i -e "s/%%$PNAME%%/$EV/g" src/opnsense/version/core
done

sshpass -p "$SSHPASS" scp src/opnsense/version/core root@${TARGETIP}:/usr/local/opnsense/version/core
