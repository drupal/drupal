<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Tests validation of Layout Builder's entity_view_display entities.
 *
 * @group layout_builder
 */
class LayoutBuilderEntityViewDisplayValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    EntityViewMode::create([
      'id' => 'user.layout',
      'label' => 'Layout',
      'targetEntityType' => 'user',
    ])->save();

    $this->entity = LayoutBuilderEntityViewDisplay::create([
      'mode' => 'layout',
      'label' => 'Layout',
      'targetEntityType' => 'user',
      'bundle' => 'user',
    ]);
    $this->entity->save();
  }

}
