#!/bin/bash

for F in $(find src -type f -follow -print|xargs ls|sed 's/src\//\/usr\/local\//g'); do
  if grep -Fxq "$F" plist; then
    true
  else
    echo $F
  fi
done
