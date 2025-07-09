<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit\Plugin\ConfigAction\Deriver;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\Plugin\ConfigAction\Deriver\AddComponentDeriver;
use Drupal\layout_builder\SectionListInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\ConfigAction\Deriver\AddComponentDeriver
 * @group layout_builder
 */
class AddComponentDeriverTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Tests derivative generation for entities implementing SectionListInterface.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions(): void {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $deriver = new AddComponentDeriver($entityTypeManager->reveal());
    $entity_types = [];

    // Create a mock entity type that implements both required interfaces.
    $valid_entity_type = $this->prophesize(EntityTypeInterface::class);
    $valid_entity_type->entityClassImplements(ConfigEntityInterface::class)->willReturn(TRUE);
    $valid_entity_type->entityClassImplements(SectionListInterface::class)->willReturn(TRUE);
    $valid_entity_type->id()->willReturn('valid_type');
    $entity_types['valid_type'] = $valid_entity_type->reveal();

    // Create a mock entity type that only implements ConfigEntityInterface.
    $config_only_type = $this->prophesize(EntityTypeInterface::class);
    $config_only_type->entityClassImplements(ConfigEntityInterface::class)->willReturn(TRUE);
    $config_only_type->entityClassImplements(SectionListInterface::class)->willReturn(FALSE);
    $entity_types['config_only'] = $config_only_type->reveal();

    // Create a mock entity type that only implements SectionListInterface.
    $section_only_type = $this->prophesize(EntityTypeInterface::class);
    $section_only_type->entityClassImplements(ConfigEntityInterface::class)->willReturn(FALSE);
    $section_only_type->entityClassImplements(SectionListInterface::class)->willReturn(TRUE);
    $entity_types['section_only'] = $section_only_type->reveal();

    $entityTypeManager->getDefinitions()->willReturn($entity_types);

    $base_plugin_definition = [];
    $derivatives = $deriver->getDerivativeDefinitions($base_plugin_definition);

    $this->assertCount(1, $derivatives, 'Only one derivative should be generated.');
    $this->assertArrayHasKey('addComponentToLayout', $derivatives, 'The derivative should be keyed by addComponentToLayout.');
    $this->assertEquals(['valid_type'], $derivatives['addComponentToLayout']['entity_types'], 'Only the valid entity type should be included in the derivative definition.');
  }

}
