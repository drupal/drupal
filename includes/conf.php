<?php
// $Id$

#
# Database settings:
#

# $db_url = "pgsql://user:password@hostname/database";
# $db_url = "mysql://user:password@hostname/database";

$db_url = "mysql://drop:drop@localhost/drop";

#
# PHP settings:
#

# Avoid "page has expired" problems when browsing from your cache or history
# after having filled out a form:
// ini_set("session.cache_limiter", "");

# If required, update PHP's include path to include your PEAR directory:
// ini_set("include_path", ".:/path/to/pear");


#
# Themes:
#
$themes = array("UnConeD" => array(
                  "themes/unconed/unconed.theme",
                  "Internet explorer, Netscape, Opera"),
                "Marvin"  => array(
                  "themes/marvin/marvin.theme",
                  "Internet explorer, Netscape, Opera"),
                "Stone Age"  => array(
                  "themes/example/example.theme",
                  "Internet explorer, Netscape, Opera, Lynx"),
                "Goofy"  => array(
                  "themes/goofy/goofy.theme",
                  "Internet explorer, Netscape, Opera"),
                "Trillian"  => array(
                  "themes/trillian/trillian.theme",
                  "Internet explorer, Netscape, Opera"));

#
# Languages / translation / internationalization:
#   The first language listed in this associative array will
#   automatically become the default language.  You can add a language
#   but make sure your SQL table, called locales is updated
#   appropriately.
$languages = array("en" => "English");

?>