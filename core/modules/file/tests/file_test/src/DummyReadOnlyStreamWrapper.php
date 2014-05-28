<?php

/**
 * @file
 * Definition of Drupal\file_test\DummyReadOnlyStreamWrapper.
 */

namespace Drupal\file_test;

use Drupal\Core\StreamWrapper\LocalReadOnlyStream;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy stream wrapper implementation (dummy-readonly://).
 */
class DummyReadOnlyStreamWrapper extends LocalReadOnlyStream {
  function getDirectoryPath() {
    return conf_path() . '/files';
  }

  /**
   * Override getInternalUri().
   *
   * Return a dummy path for testing.
   */
  function getInternalUri() {
    return '/dummy/example.txt';
  }

  /**
   * Override getExternalUrl().
   *
   * Return the HTML URI of a public file.
   */
  function getExternalUrl() {
    return '/dummy/example.txt';
  }
}
