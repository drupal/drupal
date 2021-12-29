<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Driver\pgsql\Connection;
use Drupal\Core\Database\Driver\pgsql\Delete;
use Drupal\Core\Database\Driver\pgsql\Install\Tasks;
use Drupal\Core\Database\Driver\pgsql\Insert;
use Drupal\Core\Database\Driver\pgsql\Schema;
use Drupal\Core\Database\Driver\pgsql\Select;
use Drupal\Core\Database\Driver\pgsql\Truncate;
use Drupal\Core\Database\Driver\pgsql\Update;
use Drupal\Core\Database\Driver\pgsql\Upsert;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the deprecations of the PostgreSQL database driver classes in Core.
 *
 * @group legacy
 * @group Database
 */
class PgsqlDriverLegacyTest extends DatabaseTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if ($this->connection->driver() !== 'pgsql') {
      $this->markTestSkipped('Only test the deprecation message for the PostgreSQL database driver classes in Core.');
    }
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Install\Tasks
   */
  public function testDeprecationInstallTasks() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Install\Tasks is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $tasks = new Tasks();
    $this->assertInstanceOf(Tasks::class, $tasks);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Connection
   */
  public function testDeprecationConnection() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Connection is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $connection = new Connection($this->createMock(StubPDO::class), []);
    $this->assertInstanceOf(Connection::class, $connection);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Delete
   */
  public function testDeprecationDelete() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Delete is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $delete = new Delete($this->connection, 'test');
    $this->assertInstanceOf(Delete::class, $delete);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Insert
   */
  public function testDeprecationInsert() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Insert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $insert = new Insert($this->connection, 'test');
    $this->assertInstanceOf(Insert::class, $insert);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Schema
   */
  public function testDeprecationSchema() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Schema is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $schema = new Schema($this->connection);
    $this->assertInstanceOf(Schema::class, $schema);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Select
   */
  public function testDeprecationSelect() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Select is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $select = new Select($this->connection, 'test');
    $this->assertInstanceOf(Select::class, $select);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Truncate
   */
  public function testDeprecationTruncate() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Truncate is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $truncate = new Truncate($this->connection, 'test');
    $this->assertInstanceOf(Truncate::class, $truncate);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Update
   */
  public function testDeprecationUpdate() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Update is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $update = new Update($this->connection, 'test');
    $this->assertInstanceOf(Update::class, $update);
  }

  /**
   * @covers Drupal\Core\Database\Driver\pgsql\Upsert
   */
  public function testDeprecationUpsert() {
    $this->expectDeprecation('\Drupal\Core\Database\Driver\pgsql\Upsert is deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. The PostgreSQL database driver has been moved to the pgsql module. See https://www.drupal.org/node/3129492');
    $upsert = new Upsert($this->connection, 'test');
    $this->assertInstanceOf(Upsert::class, $upsert);
  }

}
