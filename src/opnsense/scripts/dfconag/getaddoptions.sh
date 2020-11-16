#!/bin/sh

if [ $3 = "#token#" ]; then
  echo '{"token": "'$4'"}' | ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 robot@$2 get-add-options 2>&1
else
  echo '{"username": "'$3'", "password": "'$4'"}' | ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 robot@$2 get-add-options 2>&1
fi
