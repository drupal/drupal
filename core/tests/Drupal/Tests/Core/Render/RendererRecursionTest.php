<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererRecursionTest extends RendererTestBase {

  protected function setUpRenderRecursionComplexElements(): array {
    $complex_child_markup = '<p>Imagine this is a render array for an entity.</p>';
    $parent_markup = '<p>Rendered!</p>';

    $complex_child_template = [
      '#cache' => [
        'tags' => [
          'test:complex_child',
        ],
      ],
      '#lazy_builder' => ['Drupal\Tests\Core\Render\PlaceholdersTest::callback', [$this->getRandomGenerator()->string()]],
      '#create_placeholder' => TRUE,
    ];

    return [$complex_child_markup, $parent_markup, $complex_child_template];
  }

  /**
   * ::renderRoot() may not be called inside of another ::renderRoot() call.
   *
   * @covers ::renderRoot
   * @covers ::render
   * @covers ::doRender
   */
  public function testRenderRecursionWithNestedRenderRoot(): void {
    [$complex_child_markup, $parent_markup, $complex_child_template] = $this->setUpRenderRecursionComplexElements();
    $renderer = $this->renderer;
    $this->setUpRequest();

    $complex_child = $complex_child_template;
    $callable = function () use ($renderer, $complex_child) {
      $this->expectException(\LogicException::class);
      $renderer->renderRoot($complex_child);
    };

    $page = [
      'content' => [
        '#pre_render' => [
          $callable,
        ],
        '#suffix' => $parent_markup,
      ],
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
  public function testRenderRecursionWithNestedRender(): void {
    [$complex_child_markup, $parent_markup, $complex_child_template] = $this->setUpRenderRecursionComplexElements();
    $renderer = $this->renderer;
    $this->setUpRequest();

    $callable = function ($markup) {
      $this->assertStringStartsWith('<drupal-render-placeholder', (string) $markup, 'Rendered complex child output as expected, without the placeholder replaced, i.e. with just the placeholder.');
      return $markup;
    };

    $page = [
      'content' => [
        'complex_child' => $complex_child_template,
        '#post_render' => [
          $callable,
        ],
        '#suffix' => $parent_markup,
      ],
    ];
    $output = $renderer->renderRoot($page);

    $this->assertEquals('<p>This is a rendered placeholder!</p><p>Rendered!</p>', $output, 'Rendered output as expected, with the placeholder replaced.');
    $this->assertContains('test:complex_child', $page['#cache']['tags'], 'Cache tag bubbling performed.');
    $this->assertContains('dynamic_animal', array_keys($page['#attached']['drupalSettings']), 'Asset bubbling performed.');
  }

  /**
   * ::renderInIsolation() may be called from anywhere.
   *
   * Including from inside of another ::renderRoot() call.
   *
   * @covers ::renderRoot
   * @covers ::renderInIsolation
   */
  public function testRenderRecursionWithNestedRenderInIsolation(): void {
    [$complex_child_markup, $parent_markup, $complex_child_template] = $this->setUpRenderRecursionComplexElements();
    $renderer = $this->renderer;
    $this->setUpRequest();

    $complex_child = $complex_child_template;

    $callable = function ($elements) use ($renderer, $complex_child) {
      $elements['#markup'] = $renderer->renderInIsolation($complex_child);
      $this->assertEquals('<p>This is a rendered placeholder!</p>', $elements['#markup'], 'Rendered complex child output as expected, with the placeholder replaced.');
      return $elements;
    };

    $page = [
      'content' => [
        '#pre_render' => [
          $callable,
        ],
        '#suffix' => $parent_markup,
      ],
    ];
    $output = $renderer->renderRoot($page);
    $this->assertEquals('<p>This is a rendered placeholder!</p>' . $parent_markup, $output, 'Rendered output as expected, with the placeholder replaced.');
    $this->assertNotContains('test:complex_child', $page['#cache']['tags'], 'Cache tag bubbling not performed.');
    $this->assertEmpty($page['#attached'], 'Asset bubbling not performed.');
  }

}
