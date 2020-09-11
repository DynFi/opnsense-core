#!/bin/sh

echo '{"username": "'$3'", "password": "'$4'"}' | ssh -i /var/run/dfconag/key -p $1 robot@$2 get-add-options

