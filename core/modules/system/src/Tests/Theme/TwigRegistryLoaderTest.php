<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigRegistryLoaderTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Twig registry loader.
 *
 * @group Theme
 */
class TwigRegistryLoaderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('twig_theme_test', 'block');

  /**
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme_twig_registry_loader'));
    $this->twig = \Drupal::service('twig');
  }

  /**
   * Checks to see if a value is a Twig template.
   */
  public function assertTwigTemplate($value, $message = '', $group = 'Other') {
    $this->assertTrue($value instanceof \Twig_Template, $message, $group);
  }

  /**
   * Tests template discovery using the Drupal theme registry.
   */
  public function testTemplateDiscovery() {
    $this->assertTwigTemplate($this->twig->resolveTemplate('block.html.twig'), 'Found block.html.twig in block module.');
  }

  /**
   * Tests template extension and includes using the Drupal theme registry.
   */
  public function testTwigNamespaces() {
    // Test the module-provided extend and insert templates.
    $this->drupalGet('twig-theme-test/registry-loader');
    $this->assertText('This line is from twig_theme_test/templates/twig-registry-loader-test-extend.html.twig');
    $this->assertText('This line is from twig_theme_test/templates/twig-registry-loader-test-include.html.twig');

    // Enable a theme that overrides the extend and insert templates to ensure
    // they are picked up by the registry loader.
    $this->config('system.theme')
      ->set('default', 'test_theme_twig_registry_loader')
      ->save();
    $this->drupalGet('twig-theme-test/registry-loader');
    $this->assertText('This line is from test_theme_twig_registry_loader/templates/twig-registry-loader-test-extend.html.twig');
    $this->assertText('This line is from test_theme_twig_registry_loader/templates/twig-registry-loader-test-include.html.twig');
  }

}
