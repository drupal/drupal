<?php

namespace Drupal\logger_aware_test;

use Psr\Log\AbstractLogger;

/**
 * A logger stub.
 */
class LoggerStub extends AbstractLogger {

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    // Do nothing.
  }

}
