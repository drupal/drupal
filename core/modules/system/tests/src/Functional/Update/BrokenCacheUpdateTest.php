<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that a broken or out-of-date element info cache is not used.
 *
 * @group Update
 * @group legacy
 */
class BrokenCacheUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.6.0.bare.testing.php.gz',
    ];
  }

  /**
   * Ensures that a broken or out-of-date element info cache is not used.
   */
  public function testUpdate() {
    $connection = Database::getConnection();
    // Ensure \Drupal\Core\Update\UpdateKernel::fixSerializedExtensionObjects()
    // does not clear the cache.
    $connection->delete('key_value')
      ->condition('collection', 'state')
      ->condition('name', 'system.theme.data')
      ->execute();

    // Create broken element info caches entries.
    $insert = $connection->insert('cache_discovery');
    $fields = [
      'cid' => 'element_info',
      'data' => 'BROKEN',
      'expire' => -1,
      'created' => '1549505157.144',
      'serialized' => 1,
      'tags' => '',
      'checksum' => 0,
    ];
    $insert->fields($fields);
    $fields['cid'] = 'element_info_build:seven';
    $fields['tags'] = 'element_info_build';
    $insert->values(array_values($fields));
    $fields['cid'] = 'element_info_build:stark';
    $insert->values(array_values($fields));
    $insert->execute();

    $this->runUpdates();
    // Caches should have been cleared at this point.
    $count = (int) $connection->select('cache_discovery')
      ->condition('cid', ['element_info', 'element_info_build:seven', 'element_info_build:stark'], 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertSame(0, $count);
  }

}
