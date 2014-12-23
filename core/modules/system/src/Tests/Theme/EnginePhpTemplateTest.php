<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\EnginePhpTemplateTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests theme functions with PHPTemplate.
 *
 * @group Theme
 */
class EnginePhpTemplateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme_phptemplate'));
  }

  /**
   * Ensures a theme's template is overrideable based on the 'template' filename.
   */
  function testTemplateOverride() {
    $this->config('system.theme')
      ->set('default', 'test_theme_phptemplate')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden with PHPTemplate theme.', 'Template overridden by PHPTemplate file.');
  }

}
