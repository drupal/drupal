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
#
$themes = array("UnConeD" => array(
                  "themes/unconed/unconed.theme",
                  "Internet explorer, Netscape, Opera"),
                "Marvin"  => array(
                  "themes/marvin/marvin.theme",
                  "Internet explorer, Netscape, Opera"),
                "Jeroen"  => array(
                  "themes/jeroen/jeroen.theme",
                  "Internet explorer, Netscape"),
                "Stone Age"  => array(
                  "themes/example/example.theme",
                  "Internet explorer, Netscape, Opera, Lynx"),
                "Goofy"  => array(
                  "themes/goofy/goofy.theme",
                  "Internet explorer, Netscape, Opera"),
                "Yaroon" => array(
                  "themes/yaroon/yaroon.theme",
                  "Internet explorer, Netscape, Opera"));

#
# Languages / translation / internationalization:
#   The first language listed in this associative array will
#   automatically become the default language.  You can add a language
#   but make sure your SQL table, called locales is updated
#   appropriately.
$languages = array("en" => "English");

?>