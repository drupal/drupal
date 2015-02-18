<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigRawTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Twig 'raw' filter.
 *
 * @group Theme
 */
class TwigRawTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('twig_theme_test');

  /**
   * Tests the raw filter inside an autoescape tag.
   */
  public function testAutoescapeRaw() {
    $test = array(
      '#theme' => 'twig_raw_test',
      '#script' => '<script>alert("This alert is real because I will put it through the raw filter!");</script>',
    );
    $rendered = drupal_render($test);
    $this->setRawContent($rendered);
    $this->assertRaw('<script>alert("This alert is real because I will put it through the raw filter!");</script>');
  }

  /**
   * Tests autoescaping of unsafe content.
   *
   * This is one of the most important tests in Drupal itself in terms of
   * security.
   */
  public function testAutoescape() {
    $script = '<script>alert("This alert is unreal!");</script>';
    $build = [
      '#theme' => 'twig_autoescape_test',
      '#script' => $script,
    ];
    $rendered = drupal_render($build);
    $this->setRawContent($rendered);
    $this->assertEscaped($script);
  }

}
