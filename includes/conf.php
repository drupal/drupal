<?php

#
# Database settings:
#
$db_host = "localhost";
$db_user = "drop";      // username
$db_pass = "drop";      // password
$db_name = "database";  // database

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