<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Recipe\InstallConfigurator;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\Core\Recipe\InstallConfigurator
 * @group Recipe
 */
class InstallConfiguratorTest extends KernelTestBase {

  /**
   * Tests the modules and themes to be installed.
   */
  public function testDependenciesAreAutomaticallyIncluded(): void {
    $configurator = new InstallConfigurator(
      ['node', 'test_theme_depending_on_modules'],
      $this->container->get(ModuleExtensionList::class),
      $this->container->get(ThemeExtensionList::class),
    );

    // Node and its dependencies should be listed.
    $this->assertContains('node', $configurator->modules);
    $this->assertContains('text', $configurator->modules);
    $this->assertContains('field', $configurator->modules);
    $this->assertContains('filter', $configurator->modules);
    // The test theme, along with its module AND theme dependencies, should be
    // listed.
    $this->assertContains('test_theme_depending_on_modules', $configurator->themes);
    $this->assertContains('test_module_required_by_theme', $configurator->modules);
    $this->assertContains('test_another_module_required_by_theme', $configurator->modules);
    $this->assertContains('stark', $configurator->themes);
  }

}
