<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Ensures that a broken or out-of-date element info cache is not used.
 *
 * @group Update
 */
class BrokenCacheUpdateTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->ensureUpdatesToRun();
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
    $insert = $connection->upsert('cache_discovery');
    $insert->key('cid');
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
