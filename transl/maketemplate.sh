if [ -z "$1" ]; then
    echo "USAGE: ./maketemplate.sh LANG"
    exit
fi

LANG=${1}
CURDIR=$(pwd)

PERL_DIR=/usr/share/perl5
PERL_NAME=Locale/Maketext/Extract/Plugin

LOCALEDIR=/usr/local/share/locale/%%LANG%%/LC_MESSAGES

COREDIR=/home/dawid/workspace/vectus/opnsense-core

sudo cp ${CURDIR}/Volt.pm ${PERL_DIR}/${PERL_NAME}/

if [ -d ${COREDIR}/src ]; then \
  echo ">>> Scanning ${COREDIR}"; \
  xgettext.pl -P Locale::Maketext::Extract::Plugin::Volt -u -w -W -D ${COREDIR}/src -p ${CURDIR} -o ${LANG}.pot; \
  find ${COREDIR}/src -type f -print0 | \
  xargs -0 xgettext -L PHP --from-code=UTF-8 -F --strict --debug -j -o ${CURDIR}/${LANG}.pot; \
fi
