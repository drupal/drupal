#!/usr/bin/env php
<?php

/**
 * @file
 * Drupal hash script - to generate a hash from a plaintext password
 *
 * @param password1 [password2 [password3 ...]]
 *  Plain-text passwords in quotes (or with spaces backslash escaped).
 *
 * @todo Port to a console command. https://www.drupal.org/node/2289409
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  return;
}

$script = basename(array_shift($_SERVER['argv']));

if (in_array('--help', $_SERVER['argv']) || empty($_SERVER['argv'])) {
  echo <<<EOF

Generate Drupal password hashes from the shell.

Usage:        {$script} [OPTIONS] "<plaintext password>"
Example:      {$script} "my-new-password"

All arguments are long options.

  --help      Print this page.

  "<password1>" ["<password2>" ["<password3>" ...]]

              One or more plaintext passwords enclosed by double quotes. The
              output hash may be manually entered into the
              {users_field_data}.pass field to change a password via SQL to a
              known value.


EOF;
  exit;
}

// Password list to be processed.
$passwords = $_SERVER['argv'];

$autoloader = require __DIR__ . '/../../autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod', FALSE);
$kernel->boot();

$password_hasher = $kernel->getContainer()->get('password');

foreach ($passwords as $password) {
  print("\npassword: $password \t\thash: " . $password_hasher->hash($password) . "\n");
}
print("\n");
