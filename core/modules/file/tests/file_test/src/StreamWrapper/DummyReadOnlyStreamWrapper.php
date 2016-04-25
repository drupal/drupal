<?php

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalReadOnlyStream;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy stream wrapper implementation (dummy-readonly://).
 */
class DummyReadOnlyStreamWrapper extends LocalReadOnlyStream {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Dummy files (readonly)');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Dummy wrapper for simpletest (readonly).');
  }

  function getDirectoryPath() {
    return \Drupal::service('site.path') . '/files';
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
