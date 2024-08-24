<?php

declare(strict_types=1);

namespace Drupal\error_service_test\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Throws an exception while logging an exception.
 *
 * @see \Drupal\system\Tests\System\UncaughtExceptionTest::testLoggerException()
 */
class TestLog implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    $trigger = [
      '%type' => 'Exception',
      '@message' => 'Deforestation',
      '%function' => 'Drupal\error_service_test\MonkeysInTheControlRoom->handle()',
      'severity_level' => 3,
      'channel' => 'php',
    ];
    if (array_diff_assoc($trigger, $context) === []) {
      throw new \Exception('Oh, oh, frustrated monkeys!');
    }
  }

}
