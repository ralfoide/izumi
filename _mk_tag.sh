#!/bin/sh


# V="./src/version.php"

# keep only the directory portion of the calling program name
D="$0"
if [ -L "$D" ];
then
	# dereference link
	D=`ls -l "$D" | sed 's/^.*-> //'`
fi
D=${D/`basename $D`/}

V1="./src/version.php"
V=V1
if [ ! -f $V ];
then
	V="$D/$V1"
fi;


if [ -f $V ];
then
	VERS=`grep "\$izu_version = \"" $V | sed 's/.*"\([0-9i\.]*\)".*/\1/' | sed 's/\.//g'`
else
	VERS="tag_YYYY-MM-DD_v1234"
fi

DATE=`date +%Y-%m-%d | tr -d "\n"`

TAG="tag_${DATE}_v$VERS"

if [ "$1" == "" ]
then
	echo
	echo "Usage: $0 <tag>"
	echo
	echo "where tag is in the form $TAG"
	echo
	exit 1
fi

TEMP=`mktemp`

cvs -z3 rtag $2 $1 izumi | tee "$TEMP"

RETVAL=$?

echo

if [ "$RETVAL" != "0" ]
then
	echo "Error $RETVAL occured during CVS"
else
	echo "CVS done successfully"

	# the stupid CVS returns 0 even if some errors occured.
	if grep -s -q "^W .* NOT MOVING tag" "$TEMP"
	then
		echo "Suggestion: to force moving a tag, use:"
		echo "	$0 $* -F"
	fi
fi

if [ -f "$TEMP" ]
then
	rm -f "$TEMP"
fi
echo

