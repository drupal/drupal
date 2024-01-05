<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Container
 * @group DependencyInjection
 */
class ContainerTest extends UnitTestCase {

  /**
   * Tests serialization.
   */
  public function testSerialize() {
    $container = new Container();
    $this->expectException(\AssertionError::class);
    serialize($container);
  }

}
