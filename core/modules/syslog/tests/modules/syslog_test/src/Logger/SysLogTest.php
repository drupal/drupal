<?php

declare(strict_types=1);

namespace Drupal\syslog_test\Logger;

use Drupal\syslog\Logger\SysLog;
use Psr\Log\LoggerInterface;

/**
 * Redirects logging messages to error_log.
 */
class SysLogTest extends SysLog implements LoggerInterface {

  /**
   * {@inheritdoc}
   */
  protected function syslogWrapper($level, $entry) {
    $log_path = \Drupal::service('file_system')->realpath('public://syslog.log');
    error_log($entry . PHP_EOL, 3, $log_path);
  }

}
