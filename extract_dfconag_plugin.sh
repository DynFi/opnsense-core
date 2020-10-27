#!/bin/bash

CONFIG_PATH="$PWD/config.ini"
source ${CONFIG_PATH}

FILES="
opnsense/service/templates/OPNsense/DFConAg/rc.conf.d
opnsense/service/templates/OPNsense/DFConAg/+TARGETS
opnsense/service/conf/actions.d/actions_dfconag.conf
opnsense/scripts/dfconag/addme.py
opnsense/scripts/dfconag/getaddoptions.sh
opnsense/scripts/dfconag/generatekey.sh
opnsense/scripts/dfconag/reserveports.sh
opnsense/scripts/dfconag/whoami.sh
opnsense/scripts/dfconag/pretest.py
opnsense/mvc/app/views/OPNsense/DFConAg/index.volt
opnsense/mvc/app/models/OPNsense/DFConAg/DFConAg.php
opnsense/mvc/app/models/OPNsense/DFConAg/DFConAg.xml
opnsense/mvc/app/controllers/OPNsense/DFConAg/Api/SettingsController.php
opnsense/mvc/app/controllers/OPNsense/DFConAg/Api/ServiceController.php
opnsense/mvc/app/controllers/OPNsense/DFConAg/forms/settings.xml
opnsense/mvc/app/controllers/OPNsense/DFConAg/IndexController.php
etc/inc/plugins.inc.d/dfconag.inc
etc/rc.syshook.d/start/95-dfconag
etc/rc.d/dfconag
"

PWD=$(pwd)

for F in $FILES; do
  NF=`echo ${TARGETOPNSENSEPLUGINDIR}/dfconag/src/$F`
  D=$(echo $NF | sed 's/[^\/]*$//')
  echo "src/$F >> $NF ($D)"
  mkdir -p ${D}
  cp -f src/$F ${NF}
done
