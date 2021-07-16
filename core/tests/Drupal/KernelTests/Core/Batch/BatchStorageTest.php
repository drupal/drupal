<?php

namespace Drupal\KernelTests\Core\Batch;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Batch\BatchStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @coversDefaultClass \Drupal\Core\Batch\BatchStorage
 *
 * @group Batch
 */
class BatchStorageTest extends KernelTestBase {

  public function testCreate() {
    $token = $this->randomMachineName();
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce(), $token);
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */
    $id = rand();

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);
    $batch_storage->create(['id' => $id]);

    $this->assertTrue($connection->schema()->tableExists(BatchStorage::TABLE_NAME));

    $databaseData = $connection->query("SELECT * FROM {batch}")->fetchAll();

    $this->assertCount(1, $databaseData);
    $this->assertEquals($id, $databaseData[0]->bid);
    $this->assertEquals($token, $databaseData[0]->{'token'});
    $this->assertEquals('a:1:{s:2:"id";i:' . $id . ';}', $databaseData[0]->{'batch'});
  }

  public function testCreateTableCantCreate() {
    $schema = $this->getMockBuilder(Schema::class)
      ->disableOriginalConstructor()
      ->getMock();
    $schema->method('tableExists')
      ->willReturn(TRUE);
    $connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();
    $connection->method('schema')
      ->willReturn($schema);
    $connection->expects($this->once())
      ->method('insert')
      ->willThrowException(new \Exception('Fake database exception'));
    /** @var \Drupal\Core\Database\Connection $connection */

    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->never());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $this->expectException(\Exception::class);

    $batch_storage->create(['id' => 123]);
  }

  public function testCreateTableAlreadyCreated() {
    $schema = $this->getMockBuilder(Schema::class)
      ->disableOriginalConstructor()
      ->getMock();
    $schema->method('tableExists')
      ->willReturn(FALSE);
    $schema->method('createTable')
      ->willThrowException(new SchemaObjectExistsException());
    $connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();
    $connection->method('schema')
      ->willReturn($schema);
    $connection->expects($this->exactly(2))
      ->method('insert')
      ->willThrowException(new \Exception('Fake database exception'));
    /** @var \Drupal\Core\Database\Connection $connection */

    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->never());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $this->expectException(\Exception::class);

    $batch_storage->create(['id' => 123]);
  }

  public function testLoad() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();
    $batch_storage = new BatchStorage($connection, $session, $csrf_token);
    $batch_storage->create(['id' => $id]);
    $loadedBatch = $batch_storage->load($id);

    $this->assertIsArray($loadedBatch, 'Loaded batch is an array');
    $this->assertEquals($id, $loadedBatch['id'], 'Loaded batch id matches requested id');
  }

  public function testLoadMissingId() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */
    $id = rand();

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);
    $batch_storage->create(['id' => $id]);
    $this->assertFalse($batch_storage->load($id + 10));
  }

  public function testLoadDatabaseException() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();
    $batch_storage = new BatchStorage($connection, $session, $csrf_token);
    $this->assertFalse($batch_storage->load($id));
  }

  public function testLoadUnexpectedException() {
    $schema = $this->getMockBuilder(Schema::class)
      ->disableOriginalConstructor()
      ->getMock();
    $schema->method('tableExists')
      ->willReturn(TRUE);
    $connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();
    $connection->method('schema')
      ->willReturn($schema);
    $connection->method('query')
      ->willThrowException(new \Exception('Fake Exception'));
    /** @var \Drupal\Core\Database\Connection $connection */

    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();
    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $this->expectException(\Exception::class);

    $batch_storage->load($id);
  }

  public function testDelete() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();
    $batch_storage = new BatchStorage($connection, $session, $csrf_token);
    $batch_storage->create(['id' => $id]);
    $batch_storage->create(['id' => $id + 10]);

    $batch_storage->delete($id);

    $databaseData = $connection->query("SELECT * FROM {batch}")->fetchAll();
    $this->assertCount(1, $databaseData);
    $this->assertEquals($id + 10, $databaseData[0]->bid);
  }

  public function testDeleteDatabaseException() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->never());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();
    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $batch_storage->delete($id);
  }

  public function testUpdate() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();
    $temp1 = rand();
    $temp2 = $this->randomMachineName();

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $batch = ['id' => $id, 'temp' => $temp1];

    $batch_storage->create($batch);
    $this->assertEquals($temp1, $batch_storage->load($id)['temp']);

    $batch['temp'] = $temp2;
    $batch_storage->update($batch);

    $this->assertEquals($temp2, $batch_storage->load($id)['temp']);
  }

  public function testUpdateDatabaseException() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->never());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $id = rand();

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $batch_storage->update(['id' => $id, 'temp' => rand()]);
  }

  public function testCleanup() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->atLeastOnce());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $batch_storage->create(['id' => 1]);
    $batch_storage->create(['id' => 2]);

    $connection->update('batch')
      ->fields(['timestamp' => 0])
      ->condition('bid', 1)
      ->execute();

    $batch_storage->cleanup();
    $databaseData = $connection->query("SELECT * FROM {batch}")->fetchAll();

    $this->assertCount(1, $databaseData);
  }

  public function testCleanupDatabaseException() {
    $connection = Database::getConnection();
    $session = $this->getMockSession();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $csrf_token = $this->getMockCsrfTokenGenerator($this->never());
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_token */

    $batch_storage = new BatchStorage($connection, $session, $csrf_token);

    $batch_storage->cleanup();
  }

  protected function getMockSession() {
    return $this->getMockBuilder(SessionInterface::class)
      ->getMock();
  }

  protected function getMockCsrfTokenGenerator($getExpected, $token = NULL) {
    $token = is_null($token) ? $this->randomMachineName() : $token;
    $csrf_token = $this->getMockBuilder(CsrfTokenGenerator::class)
      ->disableOriginalConstructor()
      ->getMock();
    $csrf_token->expects($getExpected)
      ->method('get')
      ->with($this->isType('integer'))
      ->willReturn($token);

    return $csrf_token;
  }

}
