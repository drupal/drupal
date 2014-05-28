<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\ThemeTestTwig.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests theme functions with the Twig engine.
 */
class ThemeTestTwig extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test', 'twig_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Twig Engine',
      'description' => 'Test Twig-specific theme functionality.',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp();
    theme_enable(array('test_theme'));
  }

  /**
   * Tests that the Twig engine handles PHP data correctly.
   */
  function testTwigVariableDataTypes() {
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('twig-theme-test/php-variables');
    foreach (_test_theme_twig_php_values() as $type => $value) {
      $this->assertRaw('<li>' . $type . ': ' . $value['expected'] . '</li>');
    }
  }

}
