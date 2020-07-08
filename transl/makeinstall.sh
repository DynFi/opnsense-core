if [ -z "$1" ]; then
    echo "USAGE: ./makeinstall.sh LANGUAGE"
    exit
fi

LANGUAGE=${1}

CURDIR=$(pwd)
TARGETDIR=$CURDIR/../src/share/locale/${LANGUAGE}/LC_MESSAGES

mkdir -p $TARGETDIR

msgfmt --strict -o ${TARGETDIR}/DynFiFirewall.mo ${LANGUAGE}.po
