<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigRawTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests 'raw' Twig filter.
 */
class TwigRawTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('twig_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Twig raw filter',
      'description' => "Tests Twig 'raw' filter.",
      'group' => 'Theme',
    );
  }

  /**
   * Tests the raw filter inside an autoescape tag.
   */
  public function testAutoescapeRaw() {
    $test = array(
      '#theme' => 'twig_raw_test',
      '#script' => '<script>alert("This alert is real because I will put it through the raw filter!");</script>',
    );
    $rendered = drupal_render($test);
    $this->drupalSetContent($rendered);
    $this->assertRaw('<script>alert("This alert is real because I will put it through the raw filter!");</script>');
  }

}
