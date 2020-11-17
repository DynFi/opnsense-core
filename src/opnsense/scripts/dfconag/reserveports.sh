#!/bin/sh

if [ $3 = "#token#" ]; then
  echo '{"token": "'$4'", "mainTunnelPort": '$5', "dvTunnelPort": '$6'}' | ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 register@$2 reserve-ports 2>&1
else
  echo '{"username": "'$3'", "password": "'$4'", "mainTunnelPort": '$5', "dvTunnelPort": '$6'}' | ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 register@$2 reserve-ports 2>&1
fi
