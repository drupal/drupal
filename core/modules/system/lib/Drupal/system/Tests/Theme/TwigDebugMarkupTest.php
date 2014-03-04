<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigDebugMarkupTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for Twig debug markup.
 */
class TwigDebugMarkupTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test', 'node');

  public static function getInfo() {
    return array(
      'name' => 'Twig debug markup',
      'description' => 'Tests Twig debug markup.',
      'group' => 'Theme',
    );
  }

  /**
   * Tests debug markup added to Twig template output.
   */
  function testTwigDebugMarkup() {
    $extension = twig_extension();
    theme_enable(array('test_theme'));
    \Drupal::config('system.theme')->set('default', 'test_theme')->save();
    // Enable debug, rebuild the service container, and clear all caches.
    $this->settingsSet('twig_debug', TRUE);
    $this->rebuildContainer();
    $this->resetAll();

    $cache = $this->container->get('theme.registry')->get();
    // Create array of Twig templates.
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('theme', 'test_theme'));
    $templates += drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'node'));

    // Create a node and test different features of the debug markup.
    $node = $this->drupalCreateNode();
    $output = _theme('node', node_view($node));
    $this->assertTrue(strpos($output, '<!-- THEME DEBUG -->') !== FALSE, 'Twig debug markup found in theme output when debug is enabled.');
    $this->assertTrue(strpos($output, "CALL: _theme('node')") !== FALSE, 'Theme call information found.');
    $this->assertTrue(strpos($output, 'x node--1' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node' . $extension) !== FALSE, 'Suggested template files found in order and node ID specific template shown as current template.');
    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . $extension;
    $this->assertTrue(strpos($output, "BEGIN OUTPUT from '$template_filename'") !== FALSE, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = $this->drupalCreateNode();
    $output = _theme('node', node_view($node2));
    $this->assertTrue(strpos($output, '* node--2' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   x node' . $extension) !== FALSE, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = $this->drupalCreateNode();
    $build = array('#theme' => 'node__foo__bar');
    $build += node_view($node3);
    $output = drupal_render($build);
    $this->assertTrue(strpos($output, "CALL: _theme('node__foo__bar')") !== FALSE, 'Theme call information found.');
    $this->assertTrue(strpos($output, '* node--foo--bar' . $extension . PHP_EOL . '   * node--foo' . $extension . PHP_EOL . '   * node--3' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   x node' . $extension) !== FALSE, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $this->settingsSet('twig_debug', FALSE);
    $this->rebuildContainer();
    $this->resetAll();

    $output = _theme('node', node_view($node));
    $this->assertFalse(strpos($output, '<!-- THEME DEBUG -->') !== FALSE, 'Twig debug markup not found in theme output when debug is disabled.');
  }

}
