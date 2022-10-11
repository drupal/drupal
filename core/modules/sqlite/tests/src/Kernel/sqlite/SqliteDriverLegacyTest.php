<?php

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\Core\Database\Driver\sqlite\Connection;
use Drupal\Core\Database\Driver\sqlite\Install\Tasks;
use Drupal\Core\Database\Driver\sqlite\Insert;
use Drupal\Core\Database\Driver\sqlite\Schema;
use Drupal\Core\Database\Driver\sqlite\Select;
use Drupal\Core\Database\Driver\sqlite\Statement;
use Drupal\Core\Database\Driver\sqlite\Truncate;
use Drupal\Core\Database\Driver\sqlite\Upsert;
use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the deprecations of the SQLite database driver classes in Core.
 *
 * @group legacy
 * @group Database
 */
class SqliteDriverLegacyTest extends DriverSpecificDatabaseTestBase {

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Install\Tasks
   */
  public function testDeprecationInstallTasks() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Install\Tasks is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $tasks = new Tasks();
    $this->assertInstanceOf(Tasks::class, $tasks);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Connection
   */
  public function testDeprecationConnection() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Connection is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $connection = new Connection($this->createMock(StubPDO::class), []);
    $this->assertInstanceOf(Connection::class, $connection);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Insert
   */
  public function testDeprecationInsert() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Insert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $insert = new Insert($this->connection, 'test');
    $this->assertInstanceOf(Insert::class, $insert);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Schema
   */
  public function testDeprecationSchema() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Schema is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $schema = new Schema($this->connection);
    $this->assertInstanceOf(Schema::class, $schema);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Select
   */
  public function testDeprecationSelect() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Select is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $select = new Select($this->connection, 'test');
    $this->assertInstanceOf(Select::class, $select);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Statement
   */
  public function testDeprecationStatement() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Statement is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $statement = new Statement($this->createMock(StubPDO::class), $this->connection, '', []);
    $this->assertInstanceOf(Statement::class, $statement);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Truncate
   */
  public function testDeprecationTruncate() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Truncate is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $truncate = new Truncate($this->connection, 'test');
    $this->assertInstanceOf(Truncate::class, $truncate);
  }

  /**
   * @covers Drupal\Core\Database\Driver\sqlite\Upsert
   */
  public function testDeprecationUpsert() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\sqlite\Upsert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The SQLite database driver has been moved to the sqlite module. See https://www.drupal.org/node/3129492');
    $upsert = new Upsert($this->connection, 'test');
    $this->assertInstanceOf(Upsert::class, $upsert);
  }

}
