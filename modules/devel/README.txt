README.txt
==========

A module containing helper functions for Drupal developers and
inquisitive admins. This module can print a log of
all database queries for each page request at the bottom of each page. The
summary includes how many times each query was executed on a page, and how long
each query took.

 It also offers
 - a block for running custom PHP on a page
 - a block for quickly accessing devel pages
 - a block for masquerading as other users (useful for testing)
 - reports memory usage at bottom of page
 - A mail-system class which redirects outbound email to files
 - more

 This module is safe to use on a production site. Just be sure to only grant
 'access development information' permission to developers.

Also a dpr() function is provided, which pretty prints arrays and strings.
Useful during development. Many other nice functions like dpm(), dvm().

AJAX developers in particular ought to install FirePHP Core from
http://www.firephp.org/ and put it in the devel directory.
This happens automatically when you enable via drush. You may also
use a drush command to download the library. If downloading by hand,
your path to fb.php should look like libraries/FirePHPCore/lib/FirePHPCore/fb.php.
You can use svn checkout http://firephp.googlecode.com/svn/trunk/trunk/Libraries/FirePHPCore.
Then you can log php variables to the Firebug console. Is quite useful.

Included in this package is also:

- devel_node_access module which prints out the node_access records for a given node. Also offers hook_node_access_explain for all node access modules to implement. Handy.
- devel_generate.module which bulk creates nodes, users, comment, terms for development.

Some nifty drush integration ships with devel and devel_generate. See drush help for details.

DEVEL GENERATE EXTENSIONS
=========================
Devel Images Provider [http://drupal.org/project/devel_image_provider] allows to configure external providers for images.

COMPATIBILITY NOTES
==================
- Modules that use AHAH may have incompatibility with the query log and other
  footer info. Consider setting $GLOBALS['devel_shutdown'] = FALSE if you run into
  any issues.

DRUSH UNIT TEST
==================
See develDrushTest.php for an example of unit testing of the Drush integration.
This uses Drush's own test framework, based on PHPUnit. To run the tests, use
phpunit --bootstrap=/path/to/drush/tests/drush_testcase.inc. Note that we must name a file
under /tests there.

AUTHOR/MAINTAINER
======================
-moshe weitzman <weitzman at tejasa DOT com>
http://cyrve.com
Hans Salvisberg <drupal at salvisberg DOT com>
