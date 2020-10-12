#!/bin/sh

echo '{"username": "'$3'", "password": "'$4'"}' | ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 robot@$2 get-add-options
