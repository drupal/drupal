<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests serializing and unserializing a query.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class SerializeQueryTest extends DatabaseTestBase {

  /**
   * Confirms that a query can be serialized and unserialized.
   */
  #[IgnoreDeprecations]
  public function testSerializeQuery(): void {
    $this->expectUserDeprecationMessage('Serializing Drupal\Core\Database\Query\Query objects is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3625908');
    $this->expectUserDeprecationMessage('Unserializing Drupal\Core\Database\Query\Query objects is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3625908');

    $query = $this->connection->select('test');
    $query->addField('test', 'age');
    $query->condition('name', 'Ringo');
    // If this doesn't work, it will throw an exception, so no need for an
    // assertion.
    $query = unserialize(serialize($query));
    $results = $query->execute()->fetchCol();
    $this->assertEquals(28, $results[0], 'Query properly executed after unserialization.');
  }

}
