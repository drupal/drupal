<?php

declare(strict_types=1);

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy remote stream wrapper implementation (dummy-remote://).
 *
 * Basically just the public scheme but not returning a local file for realpath.
 */
class DummyRemoteStreamWrapper extends PublicStream {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Dummy files (remote)';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return 'Dummy wrapper for testing (remote).';
  }

  public function realpath() {
    return FALSE;
  }

}
