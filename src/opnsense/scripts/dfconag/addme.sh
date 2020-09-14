#!/bin/sh

echo '{"username": "'$3'", "password": "'$4'", "deviceGroup": "'$9'", "sshConfig": {"username": "root", "authType": "password", "secret": "'${10}'"}}' | ssh -o UserKnownHostsFile=/var/run/dfconag/known_hosts -i /var/run/dfconag/key -p $1 -R $5:localhost:$7 -R $6:localhost:$8 attach@$2 add-me
