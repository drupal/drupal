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
      'content' => ['foo' => ['type' => 'visible']],
    ]);

    // Ensure that no region is set on the component.
    $component = $entity_display->getComponent('foo');
    $this->assertArrayNotHasKey('region', $component);

    // Ensure that a region is set on the component after saving.
    $entity_display->save();
    $component = $entity_display->getComponent('foo');
    $this->assertArrayHasKey('region', $component);
    $this->assertSame('content', $component['region']);
  }

}
