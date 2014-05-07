<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\Stub\StubPDO.
 */

namespace Drupal\Tests\Core\Database\Stub;

/**
 * A stub of \PDO for testing purposes.
 *
 * We override the constructor method so that PHPUnit can mock the \PDO class.
 * \PDO itself can't be mocked, so we have to create a subclass. This subclass
 * is being used to unit test Connection, so we don't need a functional database
 * but we do need a mock \PDO object.
 *
 * @see Drupal\Tests\Core\Database\ConnectionTest
 * @see Drupal\Core\Database\Connection
 * @see http://stackoverflow.com/questions/3138946/mocking-the-pdo-object-using-phpunit
 */
class StubPDO extends \PDO {

  /**
   * Construction method.
   *
   * We override this construction method with a no-op in order to mock \PDO
   * under unit tests.
   *
   * @see http://stackoverflow.com/questions/3138946/mocking-the-pdo-object-using-phpunit
   */
  public function __construct() {
    // No-op.
  }

}
