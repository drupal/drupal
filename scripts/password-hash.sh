#!/usr/bin/env php
<?php

/**
 * Drupal hash script - to generate a hash from a plaintext password
 *
 * Check for your PHP interpreter - on Windows you'll probably have to
 * replace line 1 with
 *   #!c:/program files/php/php.exe
 *
 * @param password1 [password2 [password3 ...]]
 *  Plain-text passwords in quotes (or with spaces backslash escaped).
 */

if (version_compare(PHP_VERSION, "5.2.0", "<")) {
  $version  = PHP_VERSION;
  echo <<<EOF

ERROR: This script requires at least PHP version 5.2.0. You invoked it with
       PHP version {$version}.
\n
EOF;
  exit;
}

$script = basename(array_shift($_SERVER['argv']));

if (in_array('--help', $_SERVER['argv']) || empty($_SERVER['argv'])) {
  echo <<<EOF

Generate Drupal password hashes from the shell.

Usage:        {$script} [OPTIONS] "<plan-text password>"
Example:      {$script} "mynewpassword"

All arguments are long options.

  --help      Print this page.

  --root <path>

              Set the working directory for the script to the specified path.
              To execute this script this has to be the root directory of your
              Drupal installation, e.g. /home/www/foo/drupal (assuming Drupal
              running on Unix). Use surrounding quotation marks on Windows.

  "<password1>" ["<password2>" ["<password3>" ...]]

              One or more plan-text passwords enclosed by double quotes. The
              output hash may be manually entered into the {users}.pass field to
              change a password via SQL to a known value.

To run this script without the --root argument invoke it from the root directory
of your Drupal installation as

  ./scripts/{$script}
\n
EOF;
  exit;
}

$passwords = array();

// Parse invocation arguments.
while ($param = array_shift($_SERVER['argv'])) {
  switch ($param) {
    case '--root':
      // Change the working directory.
      $path = array_shift($_SERVER['argv']);
      if (is_dir($path)) {
        chdir($path);
      }
      break;
    default:
      // Add a password to the list to be processed.
      $passwords[] = $param;
      break;
  }
}

define('DRUPAL_ROOT', getcwd());

include_once DRUPAL_ROOT . '/includes/password.inc';
include_once DRUPAL_ROOT . '/includes/bootstrap.inc';

foreach ($passwords as $password) {
  print("\npassword: $password \t\thash: ". user_hash_password($password) ."\n");
}
print("\n");

