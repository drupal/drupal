<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the element info.
 *
 * @group Render
 */
class ElementInfoIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['test_theme', 'starterkit_theme']);
  }

  /**
   * Ensures that the element info can be altered by themes.
   */
  public function testElementInfoByTheme(): void {
    /** @var \Drupal\Core\Theme\ThemeInitializationInterface $theme_initializer */
    $theme_initializer = $this->container->get('theme.initialization');

    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $this->container->get('theme.manager');

    /** @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info */
    $element_info = $this->container->get('plugin.manager.element_info');

    $theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName('starterkit_theme'));
    $this->assertEquals(60, $element_info->getInfo('textfield')['#size']);

    $theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName('test_theme'));
    $this->assertEquals(40, $element_info->getInfo('textfield')['#size']);
  }

}
