for F in $(ls *.po | sed 's/\.po//g'); do
    echo "*** $F"
    ./maketemplate.sh $F
    ./makemerge.sh $F
    ./makeinstall.sh $F
done
rm *.pot
