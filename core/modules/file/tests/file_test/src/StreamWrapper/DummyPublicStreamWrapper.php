<?php

declare(strict_types=1);

namespace Drupal\file_test\StreamWrapper;

use Drupal\Core\Url;

/**
 * Helper class for testing the stream wrapper registry.
 *
 * Dummy stream wrapper implementation (dummy-public://).
 */
class DummyPublicStreamWrapper extends DummyStreamWrapper {

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl(): string {
    $path = str_replace('\\', '/', $this->getTarget());
    return Url::fromRoute(
      'file_test.public',
      ['filepath' => $path],
      ['absolute' => TRUE, 'path_processing' => FALSE, 'query' => ['file' => $path]]
    )->toString();
  }

}
