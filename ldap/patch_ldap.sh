#! /bin/bash

files=$(find -regextype posix-extended -regex "./[0-9]{8}.*.ldif" | sort -n)
for f in $files; do
	ldapadd -x -w h0tp0tat0 -D cn=admin,dc=sso -f $f
done

