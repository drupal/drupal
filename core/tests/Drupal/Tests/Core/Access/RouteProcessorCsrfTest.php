<?php

namespace Drupal\Tests\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\RouteProcessorCsrf;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\RouteProcessorCsrf
 * @group Access
 */
class RouteProcessorCsrfTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $csrfToken;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\Access\RouteProcessorCsrf
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();

    $this->processor = new RouteProcessorCsrf($this->csrfToken);
  }

  /**
   * Tests the processOutbound() method with no _csrf_token route requirement.
   */
  public function testProcessOutboundNoRequirement() {
    $this->csrfToken->expects($this->never())
      ->method('get');

    $route = new Route('/test-path');
    $parameters = [];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // No parameters should be added to the parameters array.
    $this->assertEmpty($parameters);
    // Cacheability of routes without a _csrf_token route requirement is
    // unaffected.
    $this->assertEquals((new BubbleableMetadata()), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with a _csrf_token route requirement.
   */
  public function testProcessOutbound() {
    $route = new Route('/test-path', [], ['_csrf_token' => 'TRUE']);
    $parameters = [];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // 'token' should be added to the parameters array.
    $this->assertArrayHasKey('token', $parameters);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = 'test-path';
    $placeholder = Crypt::hashBase64($path);
    $placeholder_render_array = [
      '#lazy_builder' => ['route_processor_csrf:renderPlaceholderCsrfToken', [$path]],
    ];
    $this->assertSame($parameters['token'], $placeholder);
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with a dynamic path and one replacement.
   */
  public function testProcessOutboundDynamicOne() {
    $route = new Route('/test-path/{slug}', [], ['_csrf_token' => 'TRUE']);
    $parameters = ['slug' => 100];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = 'test-path/100';
    $placeholder = Crypt::hashBase64($path);
    $placeholder_render_array = [
      '#lazy_builder' => ['route_processor_csrf:renderPlaceholderCsrfToken', [$path]],
    ];
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with two parameter replacements.
   */
  public function testProcessOutboundDynamicTwo() {
    $route = new Route('{slug_1}/test-path/{slug_2}', [], ['_csrf_token' => 'TRUE']);
    $parameters = ['slug_1' => 100, 'slug_2' => 'test'];

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = '100/test-path/test';
    $placeholder = Crypt::hashBase64($path);
    $placeholder_render_array = [
      '#lazy_builder' => ['route_processor_csrf:renderPlaceholderCsrfToken', [$path]],
    ];
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

}
