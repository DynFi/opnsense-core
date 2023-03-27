if [ -z "$1" ]; then
    echo "USAGE: ./maketemplate.sh LANGUAGE"
    exit
fi

CURDIR=$(pwd)
CONFIG_PATH="$CURDIR/../config.ini"
source ${CONFIG_PATH}

LANGUAGE=${1}

PERL_DIR=$(perl -V | grep 'perl5' | sed 's/^[[:blank:]]*//;s/[[:blank:]]*$//' | tail -n 1)
PERL_NAME=Locale/Maketext/Extract/Plugin
COREDIR="$CURDIR/.."

python ./Scripts/collect.py ${COREDIR}

if [ -e "${PERL_DIR}/${PERL_NAME}/Volt.pm" ]; then
    echo ">>> ${PERL_DIR}/${PERL_NAME}/Volt.pm exists"
else
    echo ">>> Copying Volt.pm to ${PERL_DIR}/${PERL_NAME}/"
    sudo cp ${CURDIR}/Volt.pm ${PERL_DIR}/${PERL_NAME}/
fi

: > ${CURDIR}/${LANGUAGE}.pot

if [ -d "${CURDIR}/src" ]; then
  echo ">>> Scanning ${CURDIR}/src"
  xgettext.pl -P Locale::Maketext::Extract::Plugin::Volt -u -w -W -D ${CURDIR}/src -p ${CURDIR} -o ${LANGUAGE}.pot
  find ${CURDIR}/src -type f -print0 | xargs -0 xgettext -L PHP --from-code=UTF-8 -F -j -o ${CURDIR}/${LANGUAGE}.pot
  cat ${LANGUAGE}.pot > ${LANGUAGE}_src.pot
fi

: > ${CURDIR}/${LANGUAGE}.pot

if [ -d "${COREDIR}/src" ]; then
  echo ">>> Scanning ${COREDIR}/src"
  xgettext.pl -P Locale::Maketext::Extract::Plugin::Volt -u -w -W -D ${COREDIR}/src -p ${CURDIR} -o ${LANGUAGE}.pot
  find ${COREDIR}/src -type f -print0 | xargs -0 xgettext -L PHP --from-code=UTF-8 -F -o ${CURDIR}/${LANGUAGE}.pot;
  cat ${LANGUAGE}.pot > ${LANGUAGE}_core.pot
fi

cat ${LANGUAGE}_core.pot > ${LANGUAGE}.pot
tail -n +19 ${LANGUAGE}_src.pot >> ${LANGUAGE}.pot

rm ${LANGUAGE}_core.pot
rm ${LANGUAGE}_src.pot

msguniq --use-first --add-location=never --to-code=utf-8 --output-file=${CURDIR}/${LANGUAGE}.pot ${CURDIR}/${LANGUAGE}.pot
