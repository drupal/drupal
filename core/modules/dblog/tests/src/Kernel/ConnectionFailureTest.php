<?php

namespace Drupal\Tests\dblog\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests logging of connection failures.
 *
 * @group dblog
 */
class ConnectionFailureTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog'];

  /**
   * Tests logging of connection failures.
   */
  public function testConnectionFailureLogging() {
    $this->installSchema('dblog', ['watchdog']);

    // MySQL errors like "1153 - Got a packet bigger than 'max_allowed_packet'
    // bytes" or "1100 - Table 'xyz' was not locked with LOCK TABLES" lead to a
    // database connection unusable for further requests. All further request
    // will result in a "2006 - MySQL server had gone away" error. As a
    // consequence it's impossible to use this connection to log the causing
    // initial error itself. Using Database::closeConnection() we simulate such
    // a corrupted connection. In this case dblog has to establish a different
    // connection by itself to be able to write the log entry.
    Database::closeConnection();

    // Create a log entry.
    $this->container->get('logger.factory')->get('php')->error('testConnectionFailureLogging');

    // Re-establish the default database connection.
    $database = Database::getConnection();

    $query = $database->select('watchdog')
      ->condition('message', 'testConnectionFailureLogging');
    $query->addExpression('MAX(wid)');
    $wid = $query->execute()->fetchField();
    $this->assertNotEmpty($wid, 'Watchdog entry has been stored in database.');
  }

}
