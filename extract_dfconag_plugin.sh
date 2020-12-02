#!/bin/bash

CONFIG_PATH="$PWD/config.ini"
source ${CONFIG_PATH}

FILES="etc/inc/plugins.inc.d/dfconag.inc
etc/rc.d/dfconag
etc/rc.syshook.d/start/95-dfconag
opnsense/mvc/app/controllers/OPNsense/DFConAg/Api/ServiceController.php
opnsense/mvc/app/controllers/OPNsense/DFConAg/Api/SettingsController.php
opnsense/mvc/app/controllers/OPNsense/DFConAg/IndexController.php
opnsense/mvc/app/models/OPNsense/DFConAg/DFConAg.php
opnsense/mvc/app/models/OPNsense/DFConAg/DFConAg.xml
opnsense/mvc/app/views/OPNsense/DFConAg/index.volt
opnsense/scripts/dfconag/addme.py
opnsense/scripts/dfconag/deleteme.py
opnsense/scripts/dfconag/disconnect.php
opnsense/scripts/dfconag/generatekey.sh
opnsense/scripts/dfconag/getaddoptions.py
opnsense/scripts/dfconag/keyscan.py
opnsense/scripts/dfconag/pretest.py
opnsense/scripts/dfconag/reserveports.py
opnsense/scripts/dfconag/whoami.py
opnsense/service/conf/actions.d/actions_dfconag.conf
opnsense/service/templates/OPNsense/DFConAg/+TARGETS
opnsense/service/templates/OPNsense/DFConAg/rc.conf.d"

PWD=$(pwd)

for F in $FILES; do
  NF=`echo ${TARGETOPNSENSEPLUGINDIR}/dfconag/src/$F`
  D=$(echo $NF | sed 's/[^\/]*$//')
  echo "src/$F >> $NF ($D)"
  mkdir -p ${D}
  cp -f src/$F ${NF}
done
