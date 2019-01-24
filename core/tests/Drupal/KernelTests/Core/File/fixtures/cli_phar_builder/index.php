<?php

/**
 * @file
 * Tests phar protection.
 */

use Drupal\Core\Security\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Exception as PharStreamWrapperException;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

// Use the current working directory so we don't have to include all the code in
// the phar file.
require_once getcwd() . '/../../../../../../../autoload.php';
stream_wrapper_unregister('phar');
stream_wrapper_register('phar', PharStreamWrapper::class);

Manager::initialize(
  (new Behavior())
    ->withAssertion(new PharExtensionInterceptor())
);

if (file_exists(__DIR__ . '/index.php')) {
  echo "Can access phar files without .phar extension if they are the CLI command.\n";
}

if (file_exists('phar://cli.phar')) {
  echo "Can access phar files with .phar extension.\n";
}

// Try an insecure phar without an extension.
try {
  file_exists('phar://cli_phar.png');
}
catch (PharStreamWrapperException $e) {
  echo "Cannot access other phar files without .phar extension.\n";
}

// Try accessing phar from a shutdown function.
register_shutdown_function('phar_shutdown');

function phar_shutdown() {
  if (file_exists(__DIR__ . '/index.php')) {
    echo "Shutdown functions work in phar files without a .phar extension.\n";
  }
  // Try an insecure phar without an extension.
  try {
    file_exists('phar://cli_phar.png');
  }
  catch (PharStreamWrapperException $e) {
    echo "Shutdown functions cannot access other phar files without .phar extension.\n";
  }
}
