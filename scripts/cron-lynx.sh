#!/bin/sh
# $Id: cron-lynx.sh,v 1.2 2005/08/11 13:02:08 dries Exp $

/usr/bin/lynx -source http://yoursite.com/cron.php > /dev/null 2>&1
