#!/bin/sh

LOCALTAG=$(git branch | grep '\-dff\-ui' | sed 's/[^0-9\.]//g' | sort -r | head -n 1)
LATESTTAG=$(git ls-remote --tags opnsense | sed 's/^[^\/]*\/tags\///' | grep -e '^19\.7\.[0-9]*$' | sort -r | head -n 1)

echo "Latest current version: $LOCALTAG"
echo "Latest OPNsense core version tag: $LATESTTAG"

git checkout $LOCALTAG
git checkout -B $LATESTTAG
git pull --no-tags opnsense $LATESTTAG

git checkout $LOCALTAG-dff-ui
git checkout -B $LATESTTAG-dff-ui

git merge $LATESTTAG
