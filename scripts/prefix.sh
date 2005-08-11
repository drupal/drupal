#!/bin/sh
# $Id: prefix.sh,v 1.4 2005/08/11 13:02:08 dries Exp $

if [ $# != 2 ]; then
  cat >&2 << EOH
This is Drupal database prefixer.

Usage:
  $0 prefix original_db.sql >prefixed_db.sql

- all tables will prefixed with 'prefix'
EOH

exit 1;
fi

PRFX=$1;
sed "s/^CREATE TABLE /CREATE TABLE $PRFX/;
     s/^INSERT INTO /INSERT INTO $PRFX/;
     s/^REPLACE /REPLACE $PRFX/;
     s/^ALTER TABLE /ALTER TABLE $PRFX/;
     s/^CREATE SEQUENCE /CREATE SEQUENCE $PRFX/;
     s/^ALTER SEQUENCE /ALTER SEQUENCE $PRFX/;
     s/^CREATE INDEX \(.*\) ON /CREATE INDEX \\1 ON $PRFX/;
     " $2

