#!/usr/local/bin/php -q
<?php

ini_set("include_path", ".:/home/dries/personal/cvs/web/pear:/home/dries/personal/cvs/web/drupal.org/x.x.x");
include_once "includes/common.inc";

/*
** Read the mail from stdin:
*/

$stdin = file("php://stdin");
$mail = implode("", $stdin);

/*
** Separate the mail headers from the mail body:
*/

list($headers, $body) = split("\n\n", $mail, 2);

/*
** Strip whitespaces, newlines and returns from the beginning and the
** end of the body.
*/

$body = trim($body);

/*
** The field-body can be split into a multiple-line representation,
** which is called "folding".  According to RFC 822, the rule is that
** wherever there may be linear whitespace (not simply LWSP-chars),
** a CRLF immediately followed by at least one LWSP-char may instead
** be inserted.  Merge multi-line headers:
*/

$data = ereg_replace("\n[ |\t]+", " ", $headers);

/*
** Parse and load the headers into an associative array:
*/

foreach (explode("\n", $data) as $line) {
  list($name, $value) = split(": ", $line, 2);
  $header[strtolower($name)] = $value;
}

/*
** Try to determine whether the mail comes from a mailing list and if
** so, which mailing list: we filter the mail based on parsing all the
** the possible mailing list headers.
*/

if (preg_match("/([^@]+)/", $header["x-mailing-list-name"], $match)) {
  $list = $match[1];  // Perl 6
}
elseif (preg_match("/owner-([^@]+)/", $header["sender"], $match)) {
  $list = $match[1];  // Majordomo
}
else if (preg_match("/([^@]+)/", $header["x-beenthere"], $match)) {
  $list = $match[1];
}
else if (preg_match("/mailing list ([^@]+)/", $header["delivered-to"], $match)) {
  $list = $match[1];
}
else if (preg_match("/<([^@]+)/", $header["x-mailing-list"], $match)) {
  $list = $match[1];
}
else if (preg_match("/([^@]+)/", $header["x-loop"], $match)) {
  $list = $match[1];
}
else if (preg_match("/([^@\.]+)/", $header["x-list-id"], $match)) {
  $list = $match[1];  // Mailman
}
else if (preg_match("/([^@\.]+)/", $header["x-list"], $match)) {
  $list = $match[1];
}
else {
  $list = "";
}

/*
** Insert the mail into the database:
*/

db_query("INSERT INTO mail (data, subject, header_from, header_to, header_cc, header_reply_to, body, list, timestamp) VALUES ('". check_query($mail) ."', '". check_query($header["subject"]) ."', '". check_query($header["from"]) ."', '". check_query($header["to"]) ."', '". check_query($header["cc"]) ."', '". check_query($header["reply-to"]) ."', '". check_query($body) ."', '". check_query($list) ."', '". check_query(time()) ."')");

?>