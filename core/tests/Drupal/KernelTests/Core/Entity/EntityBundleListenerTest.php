<?php

namespace Drupal\KernelTests\Core\Entity;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityBundleListener
 *
 * @group Entity
 */
class EntityBundleListenerTest extends EntityKernelTestBase {

  /**
   * @covers ::onBundleCreate
   *
   * Note: Installing the entity_schema_test module will mask the bug this test
   * was written to cover, as the field map cache is cleared manually by
   * \Drupal\Core\Field\FieldDefinitionListener::onFieldDefinitionCreate().
   */
  public function testOnBundleCreate() {
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
