#!/bin/sh

LATESTTAG=$(git ls-remote --tags opnsense | sed 's/^[^\/]*\/tags\///' | grep -e '^19\.7\.[0-9]*$' | sort -r | head -n 1)

echo "Latest OPNsense core version tag: $LATESTTAG"

git checkout -B $LATESTTAG
git pull --no-tags opnsense $LATESTTAG
