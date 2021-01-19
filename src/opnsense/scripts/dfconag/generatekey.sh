#!/bin/sh

if [ ! -f /var/dfconag/key ]; then
  mkdir -p /var/dfconag
  ssh-keygen -q -t ed25519 -N '' -f /var/dfconag/key
fi
