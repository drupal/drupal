<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Asset\LibraryDiscoveryIntegrationTest.
 */

namespace Drupal\system\Tests\Asset;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the element info.
 *
 * @group Render
 */
class LibraryDiscoveryIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('theme_handler')->install(['test_theme', 'classy']);
  }

  /**
   * Ensures that the element info can be altered by themes.
   */
  public function testElementInfoByTheme() {
    /** @var \Drupal\Core\Theme\ThemeInitializationInterface $theme_initializer */
    $theme_initializer = $this->container->get('theme.initialization');

    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $this->container->get('theme.manager');

    /** @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info */
    $library_discovery = $this->container->get('library.discovery');

    $theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName('test_theme'));
    $this->assertTrue($library_discovery->getLibraryByName('test_theme', 'kitten'));
  }

}
