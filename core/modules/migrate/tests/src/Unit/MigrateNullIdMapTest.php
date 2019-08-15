<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\Plugin\migrate\id_map\NullIdMap;

/**
 * Tests the NULL ID map plugin.
 *
 * @group migrate
 */
class MigrateNullIdMapTest extends MigrateTestCase {

  /**
   * Tests the NULL ID map get message iterator method.
   *
   * @group legacy
   *
   * @expectedDeprecation getMessageIterator() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use getMessages() instead. See https://www.drupal.org/node/3060969
   */
  public function testGetMessageIterator() {
    $id_map = new NullIdMap([], 'null', NULL);
    $id_map->getMessageIterator();
  }

}
