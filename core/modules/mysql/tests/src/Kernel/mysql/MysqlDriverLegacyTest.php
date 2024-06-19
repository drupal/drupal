<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\Driver\mysql\ExceptionHandler;
use Drupal\Core\Database\Driver\mysql\Install\Tasks;
use Drupal\Core\Database\Driver\mysql\Insert;
use Drupal\Core\Database\Driver\mysql\Schema;
use Drupal\Core\Database\Driver\mysql\Upsert;
use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the deprecations of the MySQL database driver classes in Core.
 *
 * @group legacy
 * @group Database
 */
class MysqlDriverLegacyTest extends DriverSpecificDatabaseTestBase {

  /**
   * @covers Drupal\Core\Database\Driver\mysql\Install\Tasks
   */
  public function testDeprecationInstallTasks(): void {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\mysql\Install\Tasks is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492');
    $tasks = new Tasks();
    $this->assertInstanceOf(Tasks::class, $tasks);
  }

  /**
   * @covers Drupal\Core\Database\Driver\mysql\Connection
   */
  public function testDeprecationConnection(): void {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\mysql\Connection is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492');
    // @todo https://www.drupal.org/project/drupal/issues/3251084 Remove setting
    // the $options parameter.
    $options['init_commands']['sql_mode'] = '';
    $connection = new Connection($this->createMock(StubPDO::class), $options);
    $this->assertInstanceOf(Connection::class, $connection);
  }

  /**
   * @covers Drupal\Core\Database\Driver\mysql\ExceptionHandler
   */
  public function testDeprecationExceptionHandler(): void {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\mysql\ExceptionHandler is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492');
    $handler = new ExceptionHandler();
    $this->assertInstanceOf(ExceptionHandler::class, $handler);
  }

  /**
   * @covers Drupal\Core\Database\Driver\mysql\Insert
   */
  public function testDeprecationInsert(): void {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\mysql\Insert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492');
    $insert = new Insert($this->connection, 'test');
    $this->assertInstanceOf(Insert::class, $insert);
  }

  /**
   * @covers Drupal\Core\Database\Driver\mysql\Schema
   */
  public function testDeprecationSchema(): void {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\mysql\Schema is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492');
    $schema = new Schema($this->connection);
    $this->assertInstanceOf(Schema::class, $schema);
  }

  /**
   * @covers Drupal\Core\Database\Driver\mysql\Upsert
   */
  public function testDeprecationUpsert(): void {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\mysql\Upsert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The MySQL database driver has been moved to the mysql module. See https://www.drupal.org/node/3129492');
    $upsert = new Upsert($this->connection, 'test');
    $this->assertInstanceOf(Upsert::class, $upsert);
  }

}
