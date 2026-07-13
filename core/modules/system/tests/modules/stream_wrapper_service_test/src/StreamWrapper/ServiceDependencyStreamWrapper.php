<?php

declare(strict_types=1);

namespace Drupal\stream_wrapper_service_test\StreamWrapper;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * A stream wrapper that depends on a service.
 */
class ServiceDependencyStreamWrapper extends PublicStream {

  /**
   * A service loaded from the global container in the constructor.
   */
  public StreamWrapperManagerInterface $streamWrapperManager;

  public function __construct() {
    $this->streamWrapperManager = \Drupal::service('stream_wrapper_service_test.dependency');
  }

}
