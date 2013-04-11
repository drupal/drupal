#!/usr/bin/env sh

# This script will run phpunit-based test classes using Drush's
# test framework.  First, the Drush executable is located, and
# then phpunit is invoked, passing in drush_testcase.inc as
# the bootstrap file.
#
# Any parameters that may be passed to phpunit may also be used
# with runtests.sh.

DRUSH_PATH="`which drush`"
DRUSH_DIRNAME="`dirname -- "$DRUSH_PATH"`"

# if [ $# = 0 ] ; then
#   phpunit --bootstrap="$DRUSH_DIRNAME/tests/drush_testcase.inc" .
# else
#   phpunit --bootstrap="$DRUSH_DIRNAME/tests/drush_testcase.inc" "$@"
# fi

#Instead, hard code target file so we don't find a simpletest file at
# /lib/Drupal/devel_generate/Tests/DevelGenerateTest.php.
phpunit --bootstrap="$DRUSH_DIRNAME/tests/drush_testcase.inc" develDrushTest.php
