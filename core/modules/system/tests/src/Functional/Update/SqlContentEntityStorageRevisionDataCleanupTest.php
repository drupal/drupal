<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Assert;

/**
 * Tests cleaning up revision data tables.
 *
 * @group Entity
 * @group Update
 */
class SqlContentEntityStorageRevisionDataCleanupTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.entity-revision-data-cleanup-2869568.php',
    ];
  }

  /**
   * Tests that stale rows in the revision data table are deleted.
   *
   * @see system_update_8404()
   */
  public function testRevisionDataCleanup() {
    // Ensure the test data exists.
    $connection = \Drupal::database();

    // There are 104 rows, 101 rows to delete plus the original 3 valid rows.
    $result = $connection->query('SELECT nid, vid, langcode FROM {node_field_revision} WHERE nid = :nid', [
      ':nid' => 8,
    ])->fetchAll();
    $this->assertCount(104, $result);

    $this->runUpdates();

    // Ensure the correct rows were deleted and only those.
    $result = $connection->query('SELECT nid, vid FROM {node_field_revision} WHERE nid = :nid AND vid = :vid ORDER BY nid, vid, langcode DESC', [
      ':nid' => 8,
      ':vid' => 8,
    ])->fetchAll();
    $this->assertEmpty($result);

    $result = $connection->query('SELECT nid, vid FROM {node_field_revision} WHERE nid = :nid AND vid = :vid ORDER BY nid, vid, langcode DESC', [
      ':nid' => 8,
      ':vid' => 9,
    ])->fetchAll();
    $this->assertEquals($result, [(object) ['nid' => '8', 'vid' => '9']]);

    // Revision 10 has two translations, ensure both records still exist.
    $result = $connection->query('SELECT nid, vid, langcode FROM {node_field_revision} WHERE nid = :nid AND vid = :vid ORDER BY nid, vid, langcode DESC', [
      ':nid' => 8,
      ':vid' => 10,
    ])->fetchAll();
    Assert::assertEquals($result, [
      (object) [
        'nid' => '8',
        'vid' => '10',
        'langcode' => 'es',
      ],
      (object) ['nid' => '8', 'vid' => '10', 'langcode' => 'en'],
    ]);

    // There should be only 3 rows left.
    $result = $connection->query('SELECT nid, vid, langcode FROM {node_field_revision} WHERE nid = :nid', [
      ':nid' => 8,
    ])->fetchAll();
    $this->assertCount(3, $result);
  }

}
