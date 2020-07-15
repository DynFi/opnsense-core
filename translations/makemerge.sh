if [ -z "$1" ]; then
    echo "USAGE: ./makemerge.sh LANGUAGE"
    exit
fi

LANGUAGE=${1}

msgmerge -U -N --backup=off ${LANGUAGE}.po ${LANGUAGE}.pot
# sed -i '' -e '/^#~.*/d' ${LANGUAGE}.po

msgfmt -o /dev/null ${LANGUAGE}.po
echo $(grep -c ^msgid ${LANGUAGE}.po) / $(grep -c ^msgstr ${LANGUAGE}.po)
