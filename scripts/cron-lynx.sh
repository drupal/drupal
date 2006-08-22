#!/bin/sh
# $Id: cron-lynx.sh,v 1.3 2006/08/22 07:38:24 dries Exp $

/usr/bin/lynx -source http://example.com/cron.php > /dev/null 2>&1
