<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\ThemeTestPhpTemplate.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests theme functions and templates with the PHPTemplate engine.
 */
class ThemeTestPhpTemplate extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'PHPTemplate Engine',
      'description' => 'Test theme functions with PHPTemplate.',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp();
    theme_enable(array('test_theme_phptemplate'));
  }

  /**
   * Ensures a theme's template is overrideable based on the 'template' filename.
   */
  function testTemplateOverride() {
    \Drupal::config('system.theme')
      ->set('default', 'test_theme_phptemplate')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden with PHPTemplate theme.', 'Template overridden by PHPTemplate file.');
  }

}
