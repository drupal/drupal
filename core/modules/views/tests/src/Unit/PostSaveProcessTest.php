<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\PostSaveProcess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\PostSaveProcess.
 */
#[CoversClass(PostSaveProcess::class)]
#[Group('views')]
class PostSaveProcessTest extends UnitTestCase {

  /**
   * Tests router rebuild flagging.
   */
  public function testNeedsRouterRebuild(): void {
    $postSaveProcess = new PostSaveProcess();
    $this->assertFalse($postSaveProcess->needsRouterRebuild());
    $postSaveProcess->setRouterRebuild();
    $this->assertTrue($postSaveProcess->needsRouterRebuild());
  }

  /**
   * Tests queueing discoveries to clear, deduplicated by class name.
   */
  public function testDiscoveriesToClear(): void {
    $postSaveProcess = new PostSaveProcess();
    $this->assertSame([], $postSaveProcess->getDiscoveriesToClear());

    // Two distinct CachedDiscoveryInterface implementations.
    [$discoveryA, $discoveryB] = \array_map(
      fn (string $class) => $this->createStub($class),
      [EntityTypeManagerInterface::class, TypedDataManagerInterface::class],
    );

    $postSaveProcess->addDiscoveryToClear($discoveryA);

    // Adding the same instance again must not create a duplicate entry.
    $postSaveProcess->addDiscoveryToClear($discoveryA);
    $this->assertCount(1, $postSaveProcess->getDiscoveriesToClear());

    // A discovery of a different class is queued as its own entry.
    $postSaveProcess->addDiscoveryToClear($discoveryB);
    $this->assertSame([
      get_class($discoveryA) => $discoveryA,
      get_class($discoveryB) => $discoveryB,
    ], $postSaveProcess->getDiscoveriesToClear());
  }

}
