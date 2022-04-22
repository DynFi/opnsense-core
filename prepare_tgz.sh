#!/bin/sh

if [ -z "$1" ]; then
    echo "USAGE: ./prepare_tgz PORTREVISION"
    exit
fi

V=${1}

F=opnsense-core-21.10.1-$V.tar
FD=opnsense-core-21.10.1-$V.tgz

echo "Preparing $F"
git archive --output=$F HEAD

# Save version of the git.

tmpfile=`mktemp /tmp/ver.XXXXXX`
echo "#!/bin/sh" > "${tmpfile}"
echo "echo \"$(./Scripts/version.sh)\"" >> "${tmpfile}"
chmod 755 "${tmpfile}"
tar --update --file=$F -s "|${tmpfile}|Scripts/version.sh|" --gid 0 --uid 0 "${tmpfile}"
rm "${tmpfile}"

echo "Building $FD"
gzip -c $F > $FD
rm $F
