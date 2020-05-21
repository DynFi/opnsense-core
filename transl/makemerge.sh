if [ -z "$1" ]; then
    echo "USAGE: ./makemerge.sh LANG"
    exit
fi

LANG=${1}

msgmerge -U -N --backup=off ${LANG}.po ${LANG}.pot
# sed -i '' -e '/^#~.*/d' ${LANG}.po

msgfmt -o /dev/null ${LANG}.po
echo $(grep -c ^msgid ${LANG}.po) / $(grep -c ^msgstr ${LANG}.po)
