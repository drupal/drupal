<?php

namespace Drupal\KernelTests\Core\Installer;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Schema;
use Drupal\Core\Installer\InstallerRedirectTrait;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\Core\Installer\InstallerRedirectTrait
 *
 * @group Installer
 * @group #slow
 */
class InstallerRedirectTraitTest extends KernelTestBase {

  /**
   * Data provider for testShouldRedirectToInstaller().
   *
   * @return array
   *   - Expected result from shouldRedirectToInstaller().
   *   - Exceptions to be handled by shouldRedirectToInstaller()
   *   - Whether or not there is a database connection.
   *   - Whether or not there is database connection info.
   *   - Whether or not there exists a sessions table in the database.
   */
  public function providerShouldRedirectToInstaller() {
    return [
      [TRUE, DatabaseNotFoundException::class, FALSE, FALSE],
      [TRUE, DatabaseNotFoundException::class, TRUE, FALSE],
      [TRUE, DatabaseNotFoundException::class, FALSE, TRUE],
      [TRUE, DatabaseNotFoundException::class, TRUE, TRUE],
      [TRUE, DatabaseNotFoundException::class, TRUE, TRUE, FALSE],

      [TRUE, \PDOException::class, FALSE, FALSE],
      [TRUE, \PDOException::class, TRUE, FALSE],
      [FALSE, \PDOException::class, FALSE, TRUE],
      [FALSE, \PDOException::class, TRUE, TRUE],
      [TRUE, \PDOException::class, TRUE, TRUE, FALSE],

      [TRUE, DatabaseExceptionWrapper::class, FALSE, FALSE],
      [TRUE, DatabaseExceptionWrapper::class, TRUE, FALSE],
      [FALSE, DatabaseExceptionWrapper::class, FALSE, TRUE],
      [FALSE, DatabaseExceptionWrapper::class, TRUE, TRUE],
      [TRUE, DatabaseExceptionWrapper::class, TRUE, TRUE, FALSE],

      [TRUE, NotFoundHttpException::class, FALSE, FALSE],
      [TRUE, NotFoundHttpException::class, TRUE, FALSE],
      [FALSE, NotFoundHttpException::class, FALSE, TRUE],
      [FALSE, NotFoundHttpException::class, TRUE, TRUE],
      [TRUE, NotFoundHttpException::class, TRUE, TRUE, FALSE],

      [FALSE, \Exception::class, FALSE, FALSE],
      [FALSE, \Exception::class, TRUE, FALSE],
      [FALSE, \Exception::class, FALSE, TRUE],
      [FALSE, \Exception::class, TRUE, TRUE],
      [FALSE, \Exception::class, TRUE, TRUE, FALSE],
    ];
  }

  /**
   * @covers ::shouldRedirectToInstaller
   * @dataProvider providerShouldRedirectToInstaller
   */
  public function testShouldRedirectToInstaller($expected, $exception, $connection, $connection_info, $session_table_exists = TRUE) {
    try {
      throw new $exception();
    }
    catch (\Exception $e) {
      // Mock the trait.
      $trait = $this->getMockBuilder(InstallerRedirectTraitMockableClass::class)
        ->onlyMethods(['isCli'])
        ->getMock();

      // Make sure that the method thinks we are not using the cli.
      $trait->expects($this->any())
        ->method('isCli')
        ->willReturn(FALSE);

      // Un-protect the method using reflection.
      $method_ref = new \ReflectionMethod($trait, 'shouldRedirectToInstaller');

      // Mock the database connection info.
      $db = $this->getMockForAbstractClass(Database::class);
      $property_ref = new \ReflectionProperty($db, 'databaseInfo');
      $property_ref->setValue($db, ['default' => $connection_info]);

      if ($connection) {
        // Mock the database connection.
        $connection = $this->getMockBuilder(Connection::class)
          ->disableOriginalConstructor()
          ->onlyMethods(['schema'])
          ->getMockForAbstractClass();

        if ($connection_info) {
          // Mock the database schema class.
          $schema = $this->getMockBuilder(Schema::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tableExists'])
            ->getMockForAbstractClass();

          $schema->expects($this->any())
            ->method('tableExists')
            ->with('sessions')
            ->willReturn($session_table_exists);

          $connection->expects($this->any())
            ->method('schema')
            ->willReturn($schema);
        }
      }
      else {
        // Set the database connection if there is none.
        $connection = NULL;
      }

      // Call shouldRedirectToInstaller.
      $this->assertSame($expected, $method_ref->invoke($trait, $e, $connection));
    }
  }

}

/**
 * A class using the InstallerRedirectTrait for mocking purposes.
 */
class InstallerRedirectTraitMockableClass {

  use InstallerRedirectTrait;

}
