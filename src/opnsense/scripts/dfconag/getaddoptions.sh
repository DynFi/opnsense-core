#!/bin/sh

echo "{\"username\": \"$3\", \"password\": \"$4\"}" | ssh -p $1 robot@$2 get-add-options
