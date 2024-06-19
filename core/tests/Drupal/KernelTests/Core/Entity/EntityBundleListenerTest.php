<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityBundleListener
 *
 * @group Entity
 */
class EntityBundleListenerTest extends EntityKernelTestBase {

  /**
   * Test bundle creation.
   *
   * Note: Installing the entity_schema_test module will mask the bug this test
   * was written to cover, as the field map cache is cleared manually by
   * \Drupal\Core\Field\FieldDefinitionListener::onFieldDefinitionCreate().
   *
   * @covers ::onBundleCreate
   */
  public function testOnBundleCreate(): void {
    $field_map = $this->container->get('entity_field.manager')->getFieldMap();
    $expected = [
      'entity_test' => 'entity_test',
    ];
    $this->assertEquals($expected, $field_map['entity_test']['id']['bundles']);

    entity_test_create_bundle('custom');
    $field_map = $this->container->get('entity_field.manager')->getFieldMap();
    $expected = [
      'entity_test' => 'entity_test',
      'custom' => 'custom',
    ];
    $this->assertSame($expected, $field_map['entity_test']['id']['bundles']);
  }

}
