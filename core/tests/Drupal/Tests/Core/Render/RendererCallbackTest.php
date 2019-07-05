<?php

namespace Drupal\Tests\Core\Render;

use Drupal\Tests\Traits\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Core\Render\Renderer
 * @group Render
 * @group legacy
 * Once Renderer::doCallback() throws exceptions this will no longer be a legacy
 * test.
 */
class RendererCallbackTest extends RendererTestBase {

  use ExpectDeprecationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->controllerResolver->expects($this->any())
      ->method('getControllerFromDefinition')
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
    $this->expectedDeprecations([$expected_deprecation]);
    $this->renderer->renderRoot($render_array);
  }

  /**
   * Data provider for testCallback().
   */
  public function providerTestCallback() {
    return [
      'Procedural function pre render' => [
        ['#pre_render' => ['\Drupal\Tests\Core\Render\callback'], '#type' => 'container'],
        'Render #pre_render callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was \Drupal\Tests\Core\Render\callback. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725',
      ],
      'Static object method post render' => [
        ['#post_render' => ['\Drupal\Tests\Core\Render\RendererCallbackTest::renderCallback'], '#type' => 'container'],
        'Render #post_render callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was \Drupal\Tests\Core\Render\RendererCallbackTest::renderCallback. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725',
      ],
      'Object method access callback' => [
        ['#access_callback' => [$this, 'renderCallback'], '#type' => 'container'],
        'Render #access_callback callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was Drupal\Tests\Core\Render\RendererCallbackTest::renderCallback. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725',
      ],
      'Procedural function lazy builder' => [
        ['#lazy_builder' => ['\Drupal\Tests\Core\Render\callback', []]],
        'Render #lazy_builder callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was \Drupal\Tests\Core\Render\callback. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725',
      ],
      'Invokable object access callback' => [
        ['#access_callback' => $this, '#type' => 'container'],
        'Render #access_callback callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was Drupal\Tests\Core\Render\RendererCallbackTest. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725',
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
