<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Installer;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubSchema;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\Core\Installer\InstallerRedirectTrait
 *
 * @group Installer
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
   *   - Whether or not there exists a sequences table in the database.
   */
  public static function providerShouldRedirectToInstaller() {
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
  public function testShouldRedirectToInstaller(bool $expected, string $exception, bool $connection, bool $connection_info, bool $sequences_table_exists = TRUE): void {
    // Mock the trait.
    $trait = $this->getMockBuilder(InstallerRedirectTraitMockableClass::class)
      ->onlyMethods(['isCli'])
      ->getMock();

    // Make sure that the method thinks we are not using the cli.
    $trait->expects($this->any())
      ->method('isCli')
      ->willReturn(FALSE);

    // If testing no connection info, we need to make the 'default' key not
    // visible.
    if (!$connection_info) {
      Database::renameConnection('default', __METHOD__);
    }

    if ($connection) {
      // Mock the database connection.
      $connection = $this->getMockBuilder(StubConnection::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['schema'])
        ->getMock();

      if ($connection_info) {
        // Mock the database schema class.
        $schema = $this->getMockBuilder(StubSchema::class)
          ->disableOriginalConstructor()
          ->onlyMethods(['tableExists'])
          ->getMock();

        $schema->expects($this->any())
          ->method('tableExists')
          ->with('sequences')
          ->willReturn($sequences_table_exists);

        $connection->expects($this->any())
          ->method('schema')
          ->willReturn($schema);
      }
    }
    else {
      // Set the database connection if there is none.
      $connection = NULL;
    }

    try {
      throw new $exception();
    }
    catch (\Exception $e) {
      // Call shouldRedirectToInstaller.
      $method_ref = new \ReflectionMethod($trait, 'shouldRedirectToInstaller');
      $this->assertSame($expected, $method_ref->invoke($trait, $e, $connection));
    }

    if (!$connection_info) {
      Database::renameConnection(__METHOD__, 'default');
    }
  }

}
