<?php

/**
 * @file
 * Definition of Drupal\file_test\DummyRemoteStreamWrapper.
 */

namespace Drupal\file_test;

use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy remote stream wrapper implementation (dummy-remote://).
 *
 * Basically just the public scheme but not returning a local file for realpath.
 */
class DummyRemoteStreamWrapper extends PublicStream {
  function realpath() {
    return FALSE;
  }
}
