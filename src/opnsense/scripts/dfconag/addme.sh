#!/bin/sh

echo '{"username": "'$3'", "password": "'$4'", "deviceGroup": "'$9'", "sshConfig": {"username": "'${10}'", "authType": "'${12}'", "secret": "'${11}'"}}' | ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 -R $5:localhost:$7 -R $6:localhost:$8 attach@$2 add-me
