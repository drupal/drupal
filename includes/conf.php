<?php

#
# Database settings:
#
$db_host = "localhost";
$db_user = "username";
$db_pass = "password";
$db_name = "database";

#
# Comment votes:
#   The keys of this associative array are displayed in each comment's
#   selection box whereas the corresponding values represent the
#   mathematical calculation to be performed to update a comment's
#   value.
#
$comment_votes = array("none" => "none",
                       "-1"   => "- 1",
                       "0"    => "+ 0",
                       "+1"   => "+ 1",
                       "+2"   => "+ 2",
                       "+3"   => "+ 3",
                       "+4"   => "+ 4",
                       "+5"   => "+ 5");

#
# Themes:
#   The first theme listed in this associative array will automatically
#   become the default theme.
#
$themes = array("UnConeD" => array(
                  "themes/unconed/unconed.theme",
                  "modern theme, gray and blue, high coolness factor"),
                "Marvin"  => array(
                  "themes/marvin/marvin.theme",
                  "classic theme, white, basic design with a fresh look"));

#
# Languages / translation / internationalization:
#   The first language listed in this associative array will
#   automatically become the default language.  You can add a language
#   but make sure your SQL table, called locales is updated
#   appropriately.
$languages = array("en" => "English");

?>