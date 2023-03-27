#!/bin/sh

mkdir -p /var/run/ntopng/
chmod 755 /var/run/ntopng
chown ntopng:ntopng /var/run/ntopng

mkdir -p /var/db/ntopng/
chmod 755 /var/db/ntopng
chown ntopng:wheel /var/db/ntopng

if [ -d /var/tmp/ntopng ]; then
  mv /var/tmp/ntopng/* /var/db/ntopng/
  rm -rf /var/tmp/ntopng
fi

if [ -z "$(service redis status)" ]; then
	exit 1
fi

/usr/local/bin/redis-cli set ntopng.prefs.log_to_file true

/usr/local/opnsense/scripts/OPNsense/Ntopng/generate_certs.php

ln -s /var/db/ntopng/ntopng.log /var/log/ntopng.log
