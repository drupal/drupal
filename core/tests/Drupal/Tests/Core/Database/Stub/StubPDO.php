<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database\Stub;

@trigger_error('\Drupal\Tests\Core\Database\Stub\StubPDO is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. No replacement provided. See https://www.drupal.org/node/3611435', E_USER_DEPRECATED);

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
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. No replacement
 *   provided.
 *
 * @see https://www.drupal.org/node/3611435
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
