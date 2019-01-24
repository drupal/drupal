<?php

/**
 * @file
 * Builds a test phar file.
 */

if (PHP_SAPI !== 'cli') {
  return;
}
// Create a phar to run from CLI.
$phar = new \Phar(__DIR__ . '/cli.phar');
$phar->buildFromDirectory(__DIR__ . '/cli_phar_builder');

// pointing main file which requires all classes
$phar->setDefaultStub('index.php', '/index.php');

// Make a version without a phar extension.
copy(__DIR__ . '/cli.phar', __DIR__ . '/cli_phar');
copy(__DIR__ . '/cli.phar', __DIR__ . '/cli_phar.png');
