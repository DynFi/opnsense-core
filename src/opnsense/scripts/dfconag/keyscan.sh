#!/bin/sh

/usr/local/bin/ssh-keyscan -p $1 $2
echo "#hashed#"
/usr/local/bin/ssh-keyscan -H -p $1 $2
