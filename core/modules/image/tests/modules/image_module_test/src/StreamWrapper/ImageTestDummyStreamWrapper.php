<?php

declare(strict_types=1);

namespace Drupal\image_module_test\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;

/**
 * A non-public, non-private local stream wrapper for image style access tests.
 *
 * Mimics a contributed stream wrapper (e.g. s3fs) so tests can
 * cover the case where image style derivatives are served from a scheme
 * that is neither core 'public' nor core 'private'.
 */
class ImageTestDummyStreamWrapper extends LocalStream {

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'Image test private files';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Image module test wrapper for non-public access checks.';
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath(): string {
    return \Drupal::getContainer()->getParameter('site.path') . '/files';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl(): string {
    return '';
  }

}
