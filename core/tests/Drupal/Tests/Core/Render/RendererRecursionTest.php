<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\RendererRecursionTest.
 */

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Render\Element;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererRecursionTest extends RendererTestBase {

  protected function setUpRenderRecursionComplexElements() {
    $complex_child_markup = '<p>Imagine this is a render array for an entity.</p>';
    $parent_markup = '<p>Rendered!</p>';

    $complex_child_template = [
      '#markup' => $complex_child_markup,
      '#attached' => [
        'library' => [
          'core/drupal',
        ],
      ],
      '#cache' => [
        'tags' => [
          'test:complex_child',
        ],
      ],
      '#post_render_cache' => [
        'Drupal\Tests\Core\Render\PostRenderCache::callback' => [
          ['foo' => $this->getRandomGenerator()->string()],
        ],
      ],
    ];

    return [$complex_child_markup, $parent_markup, $complex_child_template];
  }

  /**
   * ::renderRoot() may not be called inside of another ::renderRoot() call.
   *
   * @covers ::renderRoot
   * @covers ::render
   * @covers ::doRender
   *
   * @expectedException \LogicException
   */
  public function testRenderRecursionWithNestedRenderRoot() {
    list($complex_child_markup, $parent_markup, $complex_child_template) = $this->setUpRenderRecursionComplexElements();
    $renderer = $this->renderer;
    $this->setUpRequest();

    $complex_child = $complex_child_template;
    $callable = function () use ($renderer, $complex_child) {
      $renderer->renderRoot($complex_child);
    };

    $page = [
      'content' => [
        '#pre_render' => [
          $callable
        ],
        '#suffix' => $parent_markup,
      ]
    ];
    $renderer->renderRoot($page);
  }

  /**
   * ::render() may be called from anywhere.
   *
   * Including from inside of another ::renderRoot() call. Bubbling must be
   * performed.
   *
   * @covers ::renderRoot
   * @covers ::render
   * @covers ::doRender
   */
  public function testRenderRecursionWithNestedRender() {
    list($complex_child_markup, $parent_markup, $complex_child_template) = $this->setUpRenderRecursionComplexElements();
    $renderer = $this->renderer;
    $this->setUpRequest();

    $complex_child = $complex_child_template;

    $callable = function ($elements) use ($renderer, $complex_child, $complex_child_markup, $parent_markup) {
      $elements['#markup'] = $renderer->render($complex_child);
      $this->assertEquals($complex_child_markup, $elements['#markup'], 'Rendered complex child output as expected, without the #post_render_cache callback executed.');
      return $elements;
    };

    $page = [
      'content' => [
        '#pre_render' => [
          $callable
        ],
        '#suffix' => $parent_markup,
      ]
    ];
    $output = $renderer->renderRoot($page);

    $this->assertEquals('<p>overridden</p>', $output, 'Rendered output as expected, with the #post_render_cache callback executed.');
    $this->assertTrue(in_array('test:complex_child', $page['#cache']['tags']), 'Cache tag bubbling performed.');
    $this->assertTrue(in_array('core/drupal', $page['#attached']['library']), 'Asset bubbling performed.');
  }

  /**
   * ::renderPlain() may be called from anywhere.
   *
   * Including from inside of another ::renderRoot() call.
   *
   * @covers ::renderRoot
   * @covers ::renderPlain
   */
  public function testRenderRecursionWithNestedRenderPlain() {
    list($complex_child_markup, $parent_markup, $complex_child_template) = $this->setUpRenderRecursionComplexElements();
    $renderer = $this->renderer;
    $this->setUpRequest();

    $complex_child = $complex_child_template;

    $callable = function ($elements) use ($renderer, $complex_child, $parent_markup) {
      $elements['#markup'] = $renderer->renderPlain($complex_child);
      $this->assertEquals('<p>overridden</p>', $elements['#markup'], 'Rendered complex child output as expected, with the #post_render_cache callback executed.');
      return $elements;
    };

    $page = [
      'content' => [
        '#pre_render' => [
          $callable
        ],
        '#suffix' => $parent_markup,
      ]
    ];
    $output = $renderer->renderRoot($page);
    $this->assertEquals('<p>overridden</p>' . $parent_markup, $output, 'Rendered output as expected, with the #post_render_cache callback executed.');
    $this->assertFalse(in_array('test:complex_child', $page['#cache']['tags']), 'Cache tag bubbling not performed.');
    $this->assertTrue(empty($page['#attached']), 'Asset bubbling not performed.');
  }

}
