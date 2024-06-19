<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Unit\Action;

use Drupal\Core\Database\Connection;
use Drupal\node\Plugin\Action\AssignOwnerNode;
use Drupal\Tests\UnitTestCase;

/**
 * @group node
 * @group legacy
 */
class AssignOwnerNodeTest extends UnitTestCase {

  /**
   * Tests deprecation of \Drupal\node\Plugin\Action\AssignOwnerNodeTest.
   */
  public function testAssignOwnerNode(): void {
    $this->expectDeprecation('Drupal\node\Plugin\Action\AssignOwnerNode is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\action\Plugin\Action\AssignOwnerNode instead. See https://www.drupal.org/node/3424506');
    $this->assertIsObject(new AssignOwnerNode([], 'foo', [], $this->prophesize(Connection::class)->reveal()));
  }

}
