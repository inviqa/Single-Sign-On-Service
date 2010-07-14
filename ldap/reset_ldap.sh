#! /bin/bash

ldapdelete -x -r -w h0tp0tat0 -D cn=admin,dc=sso ou=SSO,dc=sso

ldapadd -x -w h0tp0tat0 -D cn=admin,dc=sso -f base_structure.ldif

files="Users.ldif Resources.ldif Permissions.ldif"
for f in $files; do
	if [ -r "$f" ]; then
		ldapadd -x -w h0tp0tat0 -D cn=admin,dc=sso -f $f
	fi
done

