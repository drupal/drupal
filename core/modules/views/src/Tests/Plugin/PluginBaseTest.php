<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\PluginBaseTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Markup;
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
    $tokens = ['{{ langcode }}' => Markup::create('English'), '{{ langcode__value }}' => 'en'];

    $result = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($text, $tokens) {
      return $this->testPluginBase->viewsTokenReplace($text, $tokens);
    });

    $this->assertIdentical($result, 'en means English');
  }

  /**
   * Test that the token replacement in views works correctly with dots.
   */
  public function testViewsTokenReplaceWithDots() {
    $text = '{{ argument.first }} comes before {{ argument.second }}';
    $tokens = ['{{ argument.first }}' => 'first', '{{ argument.second }}' => 'second'];

    $result = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($text, $tokens) {
      return $this->testPluginBase->viewsTokenReplace($text, $tokens);
    });

    $this->assertIdentical($result, 'first comes before second');
  }

  /**
   * Tests viewsTokenReplace without any twig tokens.
   */
  public function testViewsTokenReplaceWithTwigTokens() {
    $text = 'Just some text';
    $tokens = [];
    $result = $this->testPluginBase->viewsTokenReplace($text, $tokens);
    $this->assertIdentical($result, 'Just some text');
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
