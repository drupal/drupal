#!/usr/bin/env php
<?php

/**
 * Drupal hash script - to generate a hash from a plaintext password
 *
 * @param password1 [password2 [password3 ...]]
 *  Plain-text passwords in quotes (or with spaces backslash escaped).
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Check for $_SERVER['argv'] instead of PHP_SAPI === 'cli' to allow this script
// to be tested with the Simpletest UI test runner.
// @see \Drupal\system\Tests\System\ScriptTest
if (!isset($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
  return;
}

if (version_compare(PHP_VERSION, "5.4.2", "<")) {
  $version  = PHP_VERSION;
  echo <<<EOF

ERROR: This script requires at least PHP version 5.4.2. You invoked it with
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

  "<password1>" ["<password2>" ["<password3>" ...]]

              One or more plan-text passwords enclosed by double quotes. The
              output hash may be manually entered into the {users}.pass field to
              change a password via SQL to a known value.


EOF;
  exit;
}

// Password list to be processed.
$passwords = $_SERVER['argv'];

$autoloader = require __DIR__ . '/../vendor/autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod', FALSE);
$kernel->boot();

$password_hasher = $kernel->getContainer()->get('password');

foreach ($passwords as $password) {
  print("\npassword: $password \t\thash: ". $password_hasher->hash($password) ."\n");
}
print("\n");

