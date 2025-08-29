<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\DependencyInjection\Container.
 */
#[CoversClass(Container::class)]
#[Group('DependencyInjection')]
class ContainerTest extends UnitTestCase {

  /**
   * Tests serialization.
   */
  public function testSerialize(): void {
    $container = new Container();
    $this->expectException(\AssertionError::class);
    serialize($container);
  }

}
