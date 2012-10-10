<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SerializeQueryTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests query serialization.
 */
class SerializeQueryTest extends DatabaseTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Serialize query',
      'description' => 'Test serializing and unserializing a query.',
      'group' => 'Database',
    );
  }

  /**
   * Confirms that a query can be serialized and unserialized.
   */
  function testSerializeQuery() {
    $query = db_select('test');
    $query->addField('test', 'age');
    $query->condition('name', 'Ringo');
    // If this doesn't work, it will throw an exception, so no need for an
    // assertion.
    $query = unserialize(serialize($query));
    $results = $query->execute()->fetchCol();
    $this->assertEqual($results[0], 28, 'Query properly executed after unserialization.');
  }
}
