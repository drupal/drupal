<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Markup;
use Drupal\KernelTests\KernelTestBase;
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
  protected $testPluginBase;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->testPluginBase = new TestPluginBase();
  }

  /**
   * Tests that the token replacement in views works correctly.
   */
  public function testViewsTokenReplace(): void {
    $text = '{{ langcode__value }} means {{ langcode }}';
    $tokens = ['{{ langcode }}' => Markup::create('English'), '{{ langcode__value }}' => 'en'];

    $result = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($text, $tokens) {
      return $this->testPluginBase->viewsTokenReplace($text, $tokens);
    });

    $this->assertSame('en means English', $result);
  }

  /**
   * Tests that the token replacement in views works correctly with dots.
   */
  public function testViewsTokenReplaceWithDots(): void {
    $text = '{{ argument.first }} comes before {{ argument.second }}';
    $tokens = ['{{ argument.first }}' => 'first', '{{ argument.second }}' => 'second'];

    $result = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($text, $tokens) {
      return $this->testPluginBase->viewsTokenReplace($text, $tokens);
    });

    $this->assertSame('first comes before second', $result);

    // Test tokens with numeric indexes.
    $text = '{{ argument.0.first }} comes before {{ argument.1.second }}';
    $tokens = ['{{ argument.0.first }}' => 'first', '{{ argument.1.second }}' => 'second'];

    $result = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($text, $tokens) {
      return $this->testPluginBase->viewsTokenReplace($text, $tokens);
    });

    $this->assertSame('first comes before second', $result);
  }

  /**
   * Tests viewsTokenReplace without any twig tokens.
   */
  public function testViewsTokenReplaceWithTwigTokens(): void {
    $text = 'Just some text';
    $tokens = [];
    $result = $this->testPluginBase->viewsTokenReplace($text, $tokens);
    $this->assertSame('Just some text', $result);
  }

}

/**
 * Helper class for using the PluginBase abstract class.
 */
class TestPluginBase extends PluginBase {

  public function __construct() {
    parent::__construct([], '', []);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsTokenReplace($text, $tokens) {
    return parent::viewsTokenReplace($text, $tokens);
  }

}
