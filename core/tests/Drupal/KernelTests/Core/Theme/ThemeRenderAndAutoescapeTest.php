<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Theme\ThemeRenderAndAutoescapeTest.
 */

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the theme_render_and_autoescape() function.
 *
 * @group Theme
 */
class ThemeRenderAndAutoescapeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * @dataProvider providerTestThemeRenderAndAutoescape
   */
  public function testThemeRenderAndAutoescape($arg, $expected) {
    if (is_array($arg) && isset($arg['#type']) && $arg['#type'] === 'link') {
      $arg = Link::createFromRoute($arg['#title'], $arg['#url']);
    }

    $context = new RenderContext();
    // Use a closure here since we need to render with a render context.
    $theme_render_and_autoescape = function () use ($arg) {
      return theme_render_and_autoescape($arg);
    };
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $output = $renderer->executeInRenderContext($context, $theme_render_and_autoescape);
    $this->assertEquals($expected, $output);
    $this->assertIsString($output);
  }

  /**
   * Provide test examples.
   */
  public function providerTestThemeRenderAndAutoescape() {
    return [
      'empty string unchanged' => ['', ''],
      'simple string unchanged' => ['ab', 'ab'],
      'int (scalar) cast to string' => [111, '111'],
      'float (scalar) cast to string' => [2.10, '2.1'],
      '> is escaped' => ['>', '&gt;'],
      'Markup EM tag is unchanged' => [Markup::create('<em>hi</em>'), '<em>hi</em>'],
      'Markup SCRIPT tag is unchanged' => [Markup::create('<script>alert("hi");</script>'), '<script>alert("hi");</script>'],
      'EM tag in string is escaped' => ['<em>hi</em>', Html::escape('<em>hi</em>')],
      'type link render array is rendered' => [['#type' => 'link', '#title' => 'Text', '#url' => '<none>'], '<a href="">Text</a>'],
      'type markup with EM tags is rendered' => [['#markup' => '<em>hi</em>'], '<em>hi</em>'],
      'SCRIPT tag in string is escaped' => [
        '<script>alert(123)</script>',
        Html::escape('<script>alert(123)</script>'),
      ],
      'type plain_text render array EM tag is escaped' => [['#plain_text' => '<em>hi</em>'], Html::escape('<em>hi</em>')],
      'type hidden render array is rendered' => [['#type' => 'hidden', '#name' => 'foo', '#value' => 'bar'], "<input type=\"hidden\" name=\"foo\" value=\"bar\" />\n"],
    ];
  }

  /**
   * Ensures invalid content is handled correctly.
   */
  public function testThemeEscapeAndRenderNotPrintable() {
    $this->expectException(\Exception::class);
    theme_render_and_autoescape(new NonPrintable());
  }

  /**
   * Ensure cache metadata is bubbled when using theme_render_and_autoescape().
   */
  public function testBubblingMetadata() {
    $link = new GeneratedLink();
    $link->setGeneratedLink('<a href="http://example.com"></a>');
    $link->addCacheTags(['foo']);
    $link->addAttachments(['library' => ['system/base']]);

    $context = new RenderContext();
    // Use a closure here since we need to render with a render context.
    $theme_render_and_autoescape = function () use ($link) {
      return theme_render_and_autoescape($link);
    };
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $output = $renderer->executeInRenderContext($context, $theme_render_and_autoescape);
    $this->assertEquals('<a href="http://example.com"></a>', $output);
    /** @var \Drupal\Core\Render\BubbleableMetadata $metadata */
    $metadata = $context->pop();
    $this->assertEquals(['foo'], $metadata->getCacheTags());
    $this->assertEquals(['library' => ['system/base']], $metadata->getAttachments());
  }

  /**
   * Ensure cache metadata is bubbled when using theme_render_and_autoescape().
   */
  public function testBubblingMetadataWithRenderable() {
    $link = new Link('', Url::fromRoute('<current>'));

    $context = new RenderContext();
    // Use a closure here since we need to render with a render context.
    $theme_render_and_autoescape = function () use ($link) {
      return theme_render_and_autoescape($link);
    };
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $output = $renderer->executeInRenderContext($context, $theme_render_and_autoescape);
    $this->assertEquals('<a href="/' . urlencode('<none>') . '"></a>', $output);
    /** @var \Drupal\Core\Render\BubbleableMetadata $metadata */
    $metadata = $context->pop();
    $this->assertEquals(['route'], $metadata->getCacheContexts());
  }

}

class NonPrintable {}
