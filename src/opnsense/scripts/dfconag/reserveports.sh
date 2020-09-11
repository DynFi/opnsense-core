#!/bin/sh

echo '{"username": "'$3'", "password": "'$4'", "mainTunnelPort": '$5', "dvTunnelPort": '$6'}' | ssh -i /var/run/dfconag/key -p $1 robot@$2 reserve-ports
