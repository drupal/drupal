<?php

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the multi theme engine support.
 *
 * @group Theme
 */
class EngineNyanCatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme_nyan_cat_engine'));
  }

  /**
   * Ensures a theme's template is overridable based on the 'template' filename.
   */
  function testTemplateOverride() {
    $this->config('system.theme')
      ->set('default', 'test_theme_nyan_cat_engine')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden with Nyan Cat theme. All of them', 'Template overridden by Nyan Cat file.');
  }

}
