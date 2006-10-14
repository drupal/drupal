#!/usr/bin/perl -w
# $Id: code-style.pl,v 1.12 2006/10/14 10:03:27 dries Exp $

# Author: Alexander Schwartz (alexander.schwartz@gmx.net)
# Licence: GPL
# First version: 2001-10-15

# Originally written for Drupal (http://drupal.org/) to ensure stylish
# code.  This program tries to show as many improvements as possible with
# no false positives.

# $Id: code-style.pl,v 1.12 2006/10/14 10:03:27 dries Exp $

$comment = 0;
$program = 0;
if ($ARGV[0] eq '-debug') {
  $debug=1;
  shift (@ARGV);
}
else {
  $debug=0;
}
while (<>) {
  $org=$_;
  s/\\["']//g;
  # please don't use nested comments for now... thanks!
  # handles comments // style, but don't mess with http://
  s/\/\/[^:].*//;
  # handles comments /**/ on a single line
  s/\/\*.*\*\///g;
  # handles comments /**/ over several lines
  if ($comment == 1) {
    if (s/.*\*\///) {
      $comment = 0;
    }
    else {
      next;
    }
  }
  if (s/\/\*.*//) {
    $comment = 1;
  }
  if (/^\s*#/) {
    next;
  }

  if (s/<\?php//) {
    $program = 1;
  }
  if (/\?>/) {
    $program = 0;
  }

  # enforce "bar". foo() ."bar" syntax
  if (/^("[^"]*"|[^"])*("[^"]*")\.[^ ]/ && $program) {
    $msg = "'\".' -> '\". '";
  }
  elsif (/^("[^"]*"|[^"])*("[^"]*")\s+\./ && $program) {
    $msg = "'\" .' -> '\".'";
  }
  # enforce "bar". foo() ."bar" syntax
  elsif (/^("[^"]*"|[^"])*[^ "]\.("[^"]*")/ && $program) {
    $msg = "'.\"' -> '.\"'";
  }
  elsif (/^("[^"]*"|[^"])*[^ "]\.\s+("[^"]*")/ && $program) {
    $msg = "'. \"' -> '.\"'";
  }
  # XHTML requires closing tag
  elsif (/<br>/i) {
    $msg = "'<br>' -> '<br />'";
  }
  elsif (/\$REQUEST_URI/i) {
    $msg = "the use of REQUEST_URI is prone to XSS exploits and does not work on IIS; use request_uri() instead";
  }
  elsif (/\"REQUEST_URI\"/i) {
    $msg = "the use of REQUEST_URI is prone to XSS exploits and does not work on IIS; use request_uri() instead";
  }

  # XHTML compatibility mode suggests a blank before /
  # i.e. <br />
  elsif (/<[a-z][^>]*[^ >]\/>/i) {
    $msg = "'<foo/".">' -> '<foo />'";
  }
  # we write '{' on the same line, not on the next
  elsif (/^\s*{/ && $program) {
    $msg = "take '{' to previous line";
  }
  elsif (/([a-z])([A-Z])/) {
    $msg = "no mixed case function or variable names, use lower case and _";
  }
  elsif (/<[\/]*[A-Z]+[^>]*>/) {
    $msg = "XHTML demands tags to be lowercase";
  }

  # trying to recognize splitted lines
  # there are only a few valid last characters in programming mode,
  # only sometimes it is ( if you use if/else with a single statement

  # from here on we need no more strings
  while (s/^([^"]*)"[^"]*"/$1#/) {};
  while (s/^([^']*)'[^']*'/$1#/) {};

  # it should be 'if (' all the time
  if (/(^|[^a-zA-Z])(if|else|elseif|while|foreach|switch|return|for)\(/) {
    $msg = "'(' -> ' ('";
  }
  #elsif (/[^;{}:\s\n]\s*\n*$/ && $program && !/^[\s}]*(if|else)/) {
  #  $msg = "don't split lines";
  #}
  elsif (/\}\s*else/) {
    $msg = "'} else' -> '}\\nelse'";
  }
  elsif (/[^{\s\n]\s*\n*$/ && $program && /^\s*(if|else)/) {
    $msg = "every if/else needs a { at eol";
  }
  elsif (/([\(\[]) / && $program) {
    $msg = "'$1 ' -> '$1'";
  }
  elsif (/\S ([\)\]])/ && $program) {
    $msg = "' $1' -> '$1'";
  }
  # but no brackets
  elsif (/([a-z-A-Z_][a-zA-Z0-9_-]*)\s+\(/ && $program) {
    if ($1 ne "switch" and $1 ne "if" and $1 ne "while" and $1 ne "foreach" and $1 ne "return" and $1 ne "for" and $1 ne "elseif") {
      $msg = "'$1 (' -> '$1('";
    }
  }
  # there should be a space before '{'
  if (/[^ ]{/ && $program) {
    $msg = "missing space before '{'";
  }
  # there should be a space after ','
  elsif (/[,][^ \n\r]/ && $program) {
    $msg = "missing space after ','";
  }
  # spaces before and after, only foreach may use $foo=>bar
  elsif (/[^ =|\-|\+](\+|\-)[^ =>|\-|\+]/ && $program && !/foreach/) {
    $msg = "'$1' -> ' $1 '";
  }
  elsif (/[^ =](\*|==|\.=|=>|=|\|\|)[^ =>]/ && $program && !/foreach/) {
    $msg = "'$1' -> ' $1 '";
  }
  # ensure $bar["foo"] and $bar[$foo] and $bar[0]
  elsif (/\[[^#][^\]]*\]/ && !/\[[0-9\$][^\]]*\]/ && !/\[\]/) {
    $msg = "only [\"foo\"], [\$foo] or [0] is allowed";
  }
  # first try to find missing quotes after = in (X)HTML tags
  elsif (/<[^>]*=[a-zA-Z0-9][^>]*>/) {
    $msg = "=... -> =\"...\"";
  }
  if (defined $msg) {
    if ($debug==0) {
      print $ARGV .":". $. .": $msg : ". $org;
    }
    undef $msg;
  }
  elsif ($debug==1) {
    print $org;
  }
} continue {
  close ARGV if eof;
}
