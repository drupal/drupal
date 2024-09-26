<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Layout;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Layout\LayoutPluginManager
 * @group Layout
 */
class LayoutPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_discovery'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['test_child_theme']);
    $this->activateTheme('test_child_theme');
  }

  /**
   * Tests that layout plugins are correctly overridden.
   */
  public function testPluginOverride(): void {
    /** @var \Drupal\Core\Layout\LayoutPluginManagerInterface $layouts_manager */
    $layouts_manager = $this->container->get('plugin.manager.core.layout');
    $definitions = $layouts_manager->getDefinitions();

    $this->assertInstanceOf(LayoutDefinition::class, $definitions['theme_parent_provided_layout']);
    $this->assertSame('Child', $definitions['theme_parent_provided_layout']->getLabel()->render());
  }

  /**
   * Activates a specified theme.
   *
   * Installs the theme if not already installed and makes it the active theme.
   *
   * @param string $theme_name
   *   The name of the theme to be activated.
   */
  protected function activateTheme(string $theme_name): void {
    $this->container->get('theme_installer')->install([$theme_name]);

    /** @var \Drupal\Core\Theme\ThemeInitializationInterface $theme_initializer */
    $theme_initializer = $this->container->get('theme.initialization');

    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $this->container->get('theme.manager');

    $theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName($theme_name));
    $this->assertSame($theme_name, $theme_manager->getActiveTheme()->getName());
  }

}
