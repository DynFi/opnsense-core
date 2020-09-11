#!/bin/sh

if [ ! -f /var/run/dfconag/key ]; then
  mkdir -p /var/run/dfconag
  ssh-keygen -t rsa -N '' -f /var/run/dfconag/key
fi
