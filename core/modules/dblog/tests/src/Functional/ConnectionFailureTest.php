<?php

namespace Drupal\Tests\dblog\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests logging of connection failures.
 *
 * @group dblog
 */
class ConnectionFailureTest extends BrowserTestBase {

  public static $modules = ['dblog'];

  /**
   * Tests logging of connection failures.
   */
  public function testConnectionFailureLogging() {
    $logger = \Drupal::service('logger.factory');

    // MySQL errors like "1153 - Got a packet bigger than 'max_allowed_packet'
    // bytes" or "1100 - Table 'xyz' was not locked with LOCK TABLES" lead to a
    // database connection unusable for further requests. All further request
    // will result in an "2006 - MySQL server had gone away" error. As a
    // consequence it's impossible to use this connection to log the causing
    // initial error itself. Using Database::closeConnection() we simulate such
    // a corrupted connection. In this case dblog has to establish a different
    // connection by itself to be able to write the log entry.
    Database::closeConnection();

    // Create a log entry.
    $logger->get('php')->error('testConnectionFailureLogging');

    // Re-establish the default database connection.
    Database::getConnection();

    $wid = db_query("SELECT MAX(wid) FROM {watchdog} WHERE message = 'testConnectionFailureLogging'")->fetchField();
    $this->assertTrue($wid, 'Watchdog entry has been stored in database.');
  }

}
