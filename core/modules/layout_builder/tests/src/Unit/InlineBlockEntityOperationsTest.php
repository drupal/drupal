<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\SynchronizableInterface;
use Drupal\layout_builder\InlineBlockEntityOperations;
use Drupal\layout_builder\InlineBlockUsageInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\InlineBlockEntityOperations
 *
 * @group layout_builder
 */
class InlineBlockEntityOperationsTest extends UnitTestCase {

  /**
   * Tests calling handlePreSave() with an entity that is syncing.
   *
   * @covers ::handlePreSave
   */
  public function testPreSaveWithSyncingEntity(): void {
    $entity = $this->prophesize(SynchronizableInterface::class);
    $entity->isSyncing()->willReturn(TRUE);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $inline_block_usage = $this->prophesize(InlineBlockUsageInterface::class);
    $section_storage_manager = $this->prophesize(SectionStorageManagerInterface::class);
    $section_storage_manager->findByContext()->shouldNotBeCalled();

    $inline_block_entity_operations = new InlineBlockEntityOperations(
      $entity_type_manager->reveal(),
      $inline_block_usage->reveal(),
      $section_storage_manager->reveal()
    );
    $inline_block_entity_operations->handlePreSave($entity->reveal());
  }

}
