#!/bin/bash

CURRENTTAG=$(git rev-parse --abbrev-ref HEAD | sed 's/[^0-9\.]//g')

git diff ${CURRENTTAG}..${CURRENTTAG}-dff-ui src > dff_ui_${CURRENTTAG}.patch
