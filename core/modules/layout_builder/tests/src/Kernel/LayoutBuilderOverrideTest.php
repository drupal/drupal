<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder_override\LayoutBuilderEntityViewDisplay as LayoutBuilderEntityViewDisplayOverride;
use Drupal\layout_builder_override_dependency\LayoutBuilderEntityViewDisplay as LayoutBuilderEntityViewDisplayDependency;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test entity view display override on top of Layout Builder override.
 */
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class LayoutBuilderOverrideTest extends LayoutBuilderCompatibilityTestBase {

  /**
   * Tests installation after overriding LayoutBuilderEntityViewDisplay.
   */
  public function testLayoutBuilderOverride(): void {
    // Install the module overriding LayoutBuilderEntityViewDisplay.
    $this->container->get('module_installer')
      ->install(['layout_builder_override']);

    // Now install layout_builder module.
    $status = $this->container->get('module_installer')
      ->install(['layout_builder']);
    $this->assertEquals(TRUE, $status);

    $displays = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->loadMultiple();
    foreach ($displays as $display) {
      $this->assertInstanceOf(LayoutBuilderEntityViewDisplayOverride::class, $display);
    }
  }

  /**
   * Tests installation while overriding LayoutBuilderEntityViewDisplay.
   */
  public function testLayoutBuilderOverrideDependency(): void {
    $this->container->get('module_installer')
      ->install(['layout_builder_override_dependency']);

    // Test with the entity type manager.
    $displays = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->loadMultiple();
    foreach ($displays as $display) {
      $this->assertInstanceOf(LayoutBuilderEntityViewDisplayDependency::class, $display);
    }

    // Test with a static call (which will call
    // EntityTypeRepositoryInterface::getEntityTypeFromClass).
    $displays = LayoutBuilderEntityViewDisplay::loadMultiple();
    foreach ($displays as $display) {
      $this->assertInstanceOf(LayoutBuilderEntityViewDisplayDependency::class, $display);
    }
  }

}
