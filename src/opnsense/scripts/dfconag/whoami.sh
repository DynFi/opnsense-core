#!/bin/sh

ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/run/dfconag/known_hosts -i /var/run/dfconag/key -p $1 register@$2 who-am-i
