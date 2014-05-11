#!/usr/bin/env bash
#
# Script automatically find all PO files in the project
# and compiles them to MO binary format.
#
# Author: Adam Staněk <adam.stanek@v3net.cz>

SCRIPT_DIR=`dirname $0`

PHPCODE=$(cat <<EOF
\$container = require_once('$SCRIPT_DIR/bootstrap.php');

foreach((array) \$container->translator->dictionaries as \$dict) {
	echo \$dict->dir . "\\n";
}

exit(0);
EOF
)

DIRS=`php -r "$PHPCODE" 2> /dev/null`


if [ $? -ne 0 -o "$DIRS" == "" ]; then
	echo
	echo -e "\e[00;31mChyba. Nepodarilo se zjistit adresare s preklady.\e[00m" >&2
	echo
	exit 1
fi

while read TR_DIR; do

	if [ -d "$TR_DIR" ]; then
		echo
		echo -e "\t\e[00;32m*\e[00m  $TR_DIR"

		FOUND=false
		while read FILENAME; do
			FOUND=true
			RELPATH=${FILENAME:${#TR_DIR}+1}
			echo -e -n "\t\t\e[00;33m-  $RELPATH:\e[00m\t"
			msgfmt --output-file ${FILENAME%.*}.mo --verbose $FILENAME
		done < <(find "$TR_DIR" -name *.po)

		if ! $FOUND ; then
			echo -e "\t\t\e[00;33m-  Nenalezeny zacne soubory k prekladu\e[00m\t"
		fi

	fi

done < <(echo -e "$DIRS")

echo
