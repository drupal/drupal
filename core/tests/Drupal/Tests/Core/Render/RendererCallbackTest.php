<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Security\UntrustedCallbackException;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 */
class RendererCallbackTest extends RendererTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->callableResolver->expects($this->any())
      ->method('getCallableFromDefinition')
      ->willReturnArgument(0);
  }

  /**
   * Tests the expected deprecations are triggered by Renderer::doCallback().
   *
   * @param array $render_array
   *   The render array with a callback.
   * @param $expected_deprecation
   *   The expected deprecation message triggered whilst rendering.
   *
   * @dataProvider providerTestCallback
   */
  public function testCallback(array $render_array, $expected_deprecation) {
    $this->expectException(UntrustedCallbackException::class);
    $this->expectExceptionMessage($expected_deprecation);
    $this->renderer->renderRoot($render_array);
  }

  /**
   * Data provider for testCallback().
   */
  public function providerTestCallback() {
    return [
      'Procedural function pre render' => [
        // We specifically test an untrusted callback here. We need to let
        // PHPStan ignore it.
        // @phpstan-ignore-next-line
        ['#pre_render' => ['\Drupal\Tests\Core\Render\callback'], '#type' => 'container'],
        'Render #pre_render callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was \Drupal\Tests\Core\Render\callback. See https://www.drupal.org/node/2966725',
      ],
      'Static object method post render' => [
        // We specifically test an invalid callback here. We need to let PHPStan
        // ignore it.
        // @phpstan-ignore-next-line
        ['#post_render' => ['\Drupal\Tests\Core\Render\RendererCallbackTest::renderCallback'], '#type' => 'container'],
        'Render #post_render callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was \Drupal\Tests\Core\Render\RendererCallbackTest::renderCallback. See https://www.drupal.org/node/2966725',
      ],
      'Object method access callback' => [
        // We specifically test an invalid callback here. We need to let PHPStan
        // ignore it.
        // @phpstan-ignore-next-line
        ['#access_callback' => [$this, 'renderCallback'], '#type' => 'container'],
        'Render #access_callback callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was Drupal\Tests\Core\Render\RendererCallbackTest::renderCallback. See https://www.drupal.org/node/2966725',
      ],
      'Procedural function lazy builder' => [
        // We specifically test an untrusted callback here. We need to let
        // PHPStan ignore it.
        // @phpstan-ignore-next-line
        ['#lazy_builder' => ['\Drupal\Tests\Core\Render\callback', []]],
        'Render #lazy_builder callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was \Drupal\Tests\Core\Render\callback. See https://www.drupal.org/node/2966725',
      ],
      'Invokable object access callback' => [
        ['#access_callback' => $this, '#type' => 'container'],
        'Render #access_callback callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was Drupal\Tests\Core\Render\RendererCallbackTest. See https://www.drupal.org/node/2966725',
      ],
    ];
  }

  /**
   * A test render callback.
   */
  public static function renderCallback($element = []) {
    return $element;
  }

  /**
   * Implements magic method as a render callback.
   */
  public function __invoke($element = []) {
    return $element;
  }

}

/**
 * A test render callback.
 */
function callback($element = []) {
  return $element;
}
