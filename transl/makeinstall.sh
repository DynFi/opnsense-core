if [ -z "$1" ]; then
    echo "USAGE: ./makemerge.sh LANGUAGE"
    exit
fi

LANGUAGE=${1}

CURDIR=$(pwd)
TARGETDIR=$CURDIR/locale/${LANGUAGE}/LC_MESSAGES

mkdir -p $TARGETDIR

msgfmt --strict -o ${TARGETDIR}/DynFiFirewall.mo ${LANGUAGE}.po
