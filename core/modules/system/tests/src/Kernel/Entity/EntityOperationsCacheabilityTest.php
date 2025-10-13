<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests cacheability added by entity operations.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityOperationsCacheabilityTest extends EntityKernelTestBase {

  /**
   * Test cacheability is added via entity operations functions and hooks.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::getOperations()
   * @see \Drupal\Core\Entity\EntityListBuilder::getDefaultOperations()
   * @see \Drupal\entity_test\Hook\EntityTestHooks::entityOperation()
   * @see \Drupal\entity_test\Hook\EntityTestHooks::entityOperationAlter()
   */
  public function testEntityOperationsCacheability(): void {
    $entity = EntityTest::create(['name' => 'Test entity']);
    $entity->save();

    $listBuilder = \Drupal::entityTypeManager()->getListBuilder('entity_test');
    $cacheability = new CacheableMetadata();
    $listBuilder->getOperations($entity, $cacheability);
    $this->assertEquals([
      'entity_test_operation_tag_test',
      'entity_test_operation_alter_tag_test',
    ], $cacheability->getCacheTags());
    $this->assertEquals(['user.permissions'], $cacheability->getCacheContexts());
    $this->assertEquals(-1, $cacheability->getCacheMaxAge());
  }

}
