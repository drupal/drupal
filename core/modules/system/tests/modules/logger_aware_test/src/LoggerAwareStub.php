<?php

declare(strict_types=1);

namespace Drupal\logger_aware_test;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * A test class that implements LoggerAwareInterface.
 */
class LoggerAwareStub implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Gets the logger.
   */
  public function getLogger(): LoggerInterface {
    return $this->logger;
  }

}
