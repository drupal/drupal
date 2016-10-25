<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityDisplayBase
 *
 * @group Entity
 */
class EntityDisplayBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * @covers ::preSave
   */
  public function testPreSave() {
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'foo' => ['type' => 'visible'],
        'bar' => ['type' => 'hidden'],
        'name' => ['type' => 'hidden', 'region' => 'content'],
      ],
    ]);

    // Ensure that no region is set on the component.
    $this->assertArrayNotHasKey('region', $entity_display->getComponent('foo'));
    $this->assertArrayNotHasKey('region', $entity_display->getComponent('bar'));

    // Ensure that a region is set on the component after saving.
    $entity_display->save();

    // The component with a visible type has been assigned a region.
    $component = $entity_display->getComponent('foo');
    $this->assertArrayHasKey('region', $component);
    $this->assertSame('content', $component['region']);

    // The component with a hidden type has been removed.
    $this->assertNull($entity_display->getComponent('bar'));

    // The component with a valid region and hidden type is unchanged.
    $component = $entity_display->getComponent('name');
    $this->assertArrayHasKey('region', $component);
    $this->assertSame('content', $component['region']);
  }

}
