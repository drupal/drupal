<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests serializing and unserializing a query.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class SerializeQueryTest extends DatabaseTestBase {

  /**
   * Confirms that a query cannot be serialized.
   */
  public function testSerializeQuery(): void {
    $query = $this->connection->select('test');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageIs('Database query objects are not serializable. This probably means you are serializing an object that has a reference to a query object. Adjust your code so that is not necessary, for example by storing the query arguments or results instead.');
    serialize($query);
  }

  /**
   * Confirms that a query cannot be unserialized.
   */
  public function testUnserializeQuery(): void {
    $class = get_class($this->connection->select('test'));
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageIs('Database query objects cannot be unserialized.');
    unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));
  }

}
