<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the entity list builder.
 *
 * @group Entity
 */
class EntityListBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    EntityTest::create(['name' => 'Entity 1'])->save();
    EntityTest::create(['name' => 'Entity 2'])->save();
  }

  /**
   * Tests that an entity list can be altered.
   */
  public function testListBuilderRowAlter(): void {
    $state = $this->container->get('state');
    /** @var \Drupal\Core\Entity\EntityListBuilderInterface $list_builder */
    $list_builder = $this->container->get('entity_type.manager')->getListBuilder('entity_test');

    $state->set('entity_test.list_builder.allow_altering', FALSE);
    $render_array = $list_builder->render();
    $this->assertSame('Entity 1', $render_array['table']['#rows'][1]['label']);
    $this->assertSame('Entity 2', $render_array['table']['#rows'][2]['label']);

    $state->set('entity_test.list_builder.allow_altering', TRUE);
    $render_array = $list_builder->render();
    $this->assertSame('Altered row: Entity 1', $render_array['table']['#rows'][1]['label']);
    $this->assertSame('Altered row: Entity 2', $render_array['table']['#rows'][2]['label']);
  }

}
