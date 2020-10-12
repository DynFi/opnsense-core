#!/bin/sh

ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p $1 register@$2 who-am-i
