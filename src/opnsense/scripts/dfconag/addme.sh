#!/bin/sh

cat /var/run/dfconag.in | ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 -R $3:localhost:$5 -R $4:localhost:$6 attach@$2 add-me
rm /var/run/dfconag.in
