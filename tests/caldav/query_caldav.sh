#!/bin/bash
METHOD=$1
USR=$2
PASSWD=$3
FILE=$4
URL=$5
DEPTH=$6

curl -v --request $METHOD \
	-H "Depth: $DEPTH" \
	-H 'Content-Type: text/xml; charset="UTF-8"' \
	--data-ascii @${FILE} -u $USR:$PASSWD \
	$URL
