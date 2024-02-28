<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Functional;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Provides methods to generate log entries.
 *
 * This trait is meant to be used only by test classes.
 */
trait FakeLogEntries {

  /**
   * Generates a number of random database log events.
   *
   * @param int $count
   *   Number of watchdog entries to generate.
   * @param array $options
   *   These options are used to override the defaults for the test.
   *   An associative array containing any of the following keys:
   *   - 'channel': String identifying the log channel to be output to.
   *     If the channel is not set, the default of 'custom' will be used.
   *   - 'message': String containing a message to be output to the log.
   *     A simple default message is used if not provided.
   *   - 'variables': Array of variables that match the message string.
   *   - 'severity': Log severity level as defined in logging_severity_levels.
   *   - 'link': String linking to view the result of the event.
   *   - 'uid': Int identifying the user id for the user.
   *   - 'request_uri': String identifying the location of the request.
   *   - 'referer': String identifying the referring URL.
   *   - 'ip': String The ip address of the client machine triggering the log
   *     entry.
   *   - 'timestamp': Int unix timestamp.
   */
  private function generateLogEntries($count, $options = []) {
    global $base_root;

    $user = !empty($this->adminUser) ? $this->adminUser : new AnonymousUserSession();

    // Prepare the fields to be logged.
    $log = $options + [
      'channel'     => 'custom',
      'message'     => 'Dblog test log message',
      'variables'   => [],
      'severity'    => RfcLogLevel::NOTICE,
      'link'        => NULL,
      'uid'         => $user->id(),
      'request_uri' => $base_root . \Drupal::request()->getRequestUri(),
      'referer'     => \Drupal::request()->server->get('HTTP_REFERER'),
      'ip'          => '127.0.0.1',
      'timestamp'   => \Drupal::time()->getRequestTime(),
    ];

    $logger = $this->container->get('logger.dblog');
    $message = $log['message'] . ' Entry #';
    for ($i = 0; $i < $count; $i++) {
      $log['message'] = $message . $i;
      $logger->log($log['severity'], $log['message'], $log);
    }
  }

}
