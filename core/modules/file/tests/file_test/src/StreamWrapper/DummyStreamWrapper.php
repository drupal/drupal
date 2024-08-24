<?php

declare(strict_types=1);

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy stream wrapper implementation (dummy://).
 */
class DummyStreamWrapper extends LocalStream {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Dummy files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Dummy wrapper for testing.');
  }

  public function getDirectoryPath() {
    return \Drupal::getContainer()->getParameter('site.path') . '/files';
  }

  /**
   * Override getInternalUri().
   *
   * Return a dummy path for testing.
   */
  public function getInternalUri() {
    return '/dummy/example.txt';
  }

  /**
   * Override getExternalUrl().
   *
   * Return the HTML URI of a public file.
   */
  public function getExternalUrl() {
    return '/dummy/example.txt';
  }

}
