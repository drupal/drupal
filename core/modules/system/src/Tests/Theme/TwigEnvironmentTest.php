<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigEnvironmentTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Component\Utility\String;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the twig environment.
 *
 * @see \Drupal\Core\Template\TwigEnvironment
 * @group Twig
 */
class TwigEnvironmentTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Tests inline templates.
   */
  public function testInlineTemplate() {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');
    $this->assertEqual($environment->renderInline('test-no-context'), 'test-no-context');
    $this->assertEqual($environment->renderInline('test-with-context {{ llama }}', array('llama' => 'muuh')), 'test-with-context muuh');

    $element = array();
    $unsafe_string = '<script>alert(\'Danger! High voltage!\');</script>';
    $element['test'] = array(
      '#type' => 'inline_template',
      '#template' => 'test-with-context {{ unsafe_content }}',
      '#context' => array('unsafe_content' => $unsafe_string),
    );
    $this->assertEqual(drupal_render($element), 'test-with-context ' . String::checkPlain($unsafe_string));

    // Enable twig_auto_reload and twig_debug.
    $settings = Settings::getAll();
    $settings['twig_debug'] = TRUE;
    $settings['twig_auto_reload'] = TRUE;

    new Settings($settings);
    $this->container = $this->kernel->rebuildContainer();
    \Drupal::setContainer($this->container);

    $element = array();
    $element['test'] = array(
      '#type' => 'inline_template',
      '#template' => 'test-with-context {{ llama }}',
      '#context' => array('llama' => 'muuh'),
    );
    $element_copy = $element;
    // Render it twice so that twig caching is triggered.
    $this->assertEqual(drupal_render($element), 'test-with-context muuh');
    $this->assertEqual(drupal_render($element_copy), 'test-with-context muuh');
  }

}

