#!/bin/sh

mkdir -p /var/run/nprobe/
chmod 755 /var/run/nprobe
chown nprobe:nprobe /var/run/nprobe

touch /var/log/nprobe.log
chown nprobe:wheel /var/log/nprobe.log
