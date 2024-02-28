<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests system_update_10101() upgrade path.
 *
 * @group system
 * @group legacy
 */
class BatchBidSerialUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the change of the {batch} table [bid] field to serial.
   */
  public function testUpdate(): void {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');

    // Before the update, inserting a record in the {batch} table without
    // passing a value for [bid] should fail, with the exception of the SQLite
    // database where a NOT NULL integer field that is the primary key is set
    // to automatic increment anyway.
    //
    // @see https://www.drupal.org/project/drupal/issues/2665216#comment-14885361
    try {
      $connection->insert('batch')
        ->fields([
          'timestamp' => \Drupal::time()->getRequestTime(),
          'token' => '',
          'batch' => NULL,
        ])
        ->execute();
      if ($connection->databaseType() !== 'sqlite') {
        $this->fail('Insert to {batch} without bid should have failed, but it did not');
      }
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(IntegrityConstraintViolationException::class, $e);
    }

    $this->runUpdates();

    // $bid should be higher than one, since the update process would have
    // executed a batch already. We look at the records inserted to determine
    // the value of $bid, instead of relying on the value returned by the
    // INSERT, because in PostgreSql the test connection gets confused by the
    // ::changeField() executed in the SUT and keeps returning 0 instead of
    // lastId as result of the insert.
    $connection->insert('batch')
      ->fields([
        'timestamp' => \Drupal::time()->getRequestTime(),
        'token' => '',
        'batch' => NULL,
      ])
      ->execute();
    $bid = (int) $connection->query('SELECT MAX([bid]) FROM {batch}')->fetchField();
    $this->assertGreaterThan(1, $bid);
    $connection->insert('batch')
      ->fields([
        'timestamp' => \Drupal::time()->getRequestTime(),
        'token' => '',
        'batch' => NULL,
      ])
      ->execute();
    $this->assertEquals($bid + 1, (int) $connection->query('SELECT MAX([bid]) FROM {batch}')->fetchField());
  }

}
