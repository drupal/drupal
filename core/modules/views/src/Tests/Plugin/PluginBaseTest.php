<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\PluginBaseTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\SafeString;
use Drupal\simpletest\KernelTestBase;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Tests the PluginBase class.
 *
 * @group views
 */
class PluginBaseTest extends KernelTestBase {

  /**
   * @var TestPluginBase
   */
  var $testPluginBase;

  public function setUp() {
    parent::setUp();
    $this->testPluginBase = new TestPluginBase();
  }

  /**
   * Test that the token replacement in views works correctly.
   */
  public function testViewsTokenReplace() {
    $text = '{{ langcode__value }} means {{ langcode }}';
    $tokens = ['{{ langcode }}' => SafeString::create('English'), '{{ langcode__value }}' => 'en'];

    $result = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($text, $tokens) {
      return $this->testPluginBase->viewsTokenReplace($text, $tokens);
    });

    $this->assertIdentical($result, 'en means English');
  }

}

/**
 * Helper class for using the PluginBase abstract class.
 */
class TestPluginBase extends PluginBase {

  public function __construct() {
    parent::__construct([], '', []);
  }

  public function viewsTokenReplace($text, $tokens) {
    return parent::viewsTokenReplace($text, $tokens);
  }

}
