<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\EngineTwigTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Twig-specific theme functionality.
 *
 * @group Theme
 */
class EngineTwigTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test', 'twig_theme_test');

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
