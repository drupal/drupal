<?php

namespace Drupal\system\Tests\Entity\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

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
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.entity-revision-data-cleanup-2869568.php',
    ];
  }

  /**
   * Tests that stale rows in the revision data table are deleted.
   */
  public function testRevisionDataCleanup() {
    // Ensure the test data exists.
    $connection = \Drupal::database();
    $result = $connection->query('SELECT nid, vid FROM {node_field_revision} WHERE nid = :nid AND vid = :vid', ['nid' => 8, 'vid' => 8])->fetchAll();
    $this->assertEqual($result, [(object) ['nid' => '8', 'vid' => '8']]);

    $this->runUpdates();

    // Ensure the correct rows were deleted and only those.
    $result = $connection->query('SELECT nid, vid FROM {node_field_revision} WHERE nid = :nid AND vid = :vid', ['nid' => 8, 'vid' => 8])->fetchAll();
    $this->assertTrue(empty($result));

    $result = $connection->query('SELECT nid, vid FROM {node_field_revision} WHERE nid = :nid AND vid = :vid', ['nid' => 8, 'vid' => 9])->fetchAll();
    $this->assertEqual($result, [(object) ['nid' => '8', 'vid' => '9']]);

    // Revision 10 has two translations, ensure both records still exist.
    $result = $connection->query('SELECT nid, vid, langcode FROM {node_field_revision} WHERE nid = :nid AND vid = :vid', ['nid' => 8, 'vid' => 10])->fetchAll();
    $this->assertEqual($result, [(object) ['nid' => '8', 'vid' => '10', 'langcode' => 'es'], (object) ['nid' => '8', 'vid' => '10', 'langcode' => 'en']]);
  }

}
