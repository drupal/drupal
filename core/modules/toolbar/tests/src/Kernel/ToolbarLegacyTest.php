<?php

namespace Drupal\Tests\toolbar\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Toolbar module's legacy code.
 *
 * @group toolbar
 * @group legacy
 */
class ToolbarLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Generate menu links from system.links.menu.yml.
    \Drupal::service('router.builder')->rebuild();

  }

  /**
   * Tests toolbar_prerender_toolbar_administration_tray() deprecation.
   *
   * @expectedDeprecation toolbar_prerender_toolbar_administration_tray() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal\toolbar\Controller\ToolbarController::preRenderAdministrationTray() instead. See https://www.drupal.org/node/2966725
   */
  public function testPreRenderToolbarAdministrationTray() {
    $render = toolbar_prerender_toolbar_administration_tray([]);
    $this->assertEquals('admin', $render['administration_menu']['#menu_name']);
  }

  /**
   * Tests _toolbar_do_get_rendered_subtrees() deprecation.
   *
   * @expectedDeprecation _toolbar_do_get_rendered_subtrees() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use Drupal\toolbar\Controller\ToolbarController::preRenderGetRenderedSubtrees() instead. See https://www.drupal.org/node/2966725
   */
  public function testDoGetRenderedSubtrees() {
    $render = _toolbar_do_get_rendered_subtrees([]);
    $this->assertEquals(['front' => ''], $render['#subtrees']);
  }

}
