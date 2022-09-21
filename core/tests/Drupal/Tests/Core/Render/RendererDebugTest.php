<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use function preg_replace;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererDebugTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->rendererConfig['debug'] = TRUE;

    parent::setUp();
  }

  /**
   * Test render debug output.
   */
  public function testDebugOutput() {
    $this->setUpRequest();
    $this->setupMemoryCache();

    $element = [
      '#cache' => [
        'keys' => ['render_cache_test_key'],
        'tags' => ['render_cache_test_tag', 'render_cache_test_tag1'],
        'max-age' => 10,
      ],
      '#markup' => 'Test 1',
    ];
    $markup = $this->renderer->renderRoot($element);

    $expected = <<<EOF
<!-- START RENDERER -->
<!-- CACHE-HIT: No -->
<!-- CACHE TAGS:
   * render_cache_test_tag
   * render_cache_test_tag1
-->
<!-- CACHE CONTEXTS:
   * languages:language_interface
   * theme
-->
<!-- CACHE KEYS:
   * render_cache_test_key
-->
<!-- CACHE MAX-AGE: 10 -->
<!-- PRE-BUBBLING CACHE TAGS:
   * render_cache_test_tag
   * render_cache_test_tag1
-->
<!-- PRE-BUBBLING CACHE CONTEXTS:
   * languages:language_interface
   * theme
-->
<!-- PRE-BUBBLING CACHE KEYS:
   * render_cache_test_key
-->
<!-- PRE-BUBBLING CACHE MAX-AGE: 10 -->
<!-- RENDERING TIME: 0.123456789 -->
Test 1
<!-- END RENDERER -->
EOF;
    $this->assertSame($expected, preg_replace('/RENDERING TIME: \d{1}.\d{9}/', 'RENDERING TIME: 0.123456789', $markup->__toString()));

    $element = [
      '#cache' => [
        'keys' => ['render_cache_test_key'],
        'tags' => ['render_cache_test_tag', 'render_cache_test_tag1'],
        'max-age' => 10,
      ],
      '#markup' => 'Test 1',
    ];
    $markup = $this->renderer->renderRoot($element);

    $this->assertStringContainsString('CACHE-HIT: Yes', $markup->__toString());
  }

}
