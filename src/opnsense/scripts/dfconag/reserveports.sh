#!/bin/sh

echo '{"username": "'$3'", "password": "'$4'", "mainTunnelPort": '$5', "dvTunnelPort": '$6'}' | ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 register@$2 reserve-ports
