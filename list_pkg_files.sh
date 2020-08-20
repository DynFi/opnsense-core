#!/bin/bash

for F in $(find src -type f -follow -print|xargs ls|sed 's/src\///g'); do
  if grep -Fxq "$F" pkg-plist; then
    true
  else
    echo $F
  fi
done
