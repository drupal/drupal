<?php

namespace Drupal\Tests\color\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Color module's legacy code.
 *
 * @group color
 * @group legacy
 */
class ColorLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'color'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['bartik']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();
  }

  /**
   * Tests color_block_view_pre_render() deprecation.
   *
   * @expectedDeprecation color_block_view_pre_render() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal\color\ColorSystemBrandingBlockAlter::preRender() instead. See https://www.drupal.org/node/2966725
   */
  public function testColorSystemBrandingBlockAlterPreRender() {
    $render = color_block_view_pre_render([]);
    $this->assertEquals(['config:color.theme.bartik'], $render['#cache']['tags']);
  }

}
