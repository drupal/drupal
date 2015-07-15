<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\RouteProcessorCsrfTest.
 */

namespace Drupal\Tests\Core\Access;

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
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\Access\RouteProcessorCsrf
   */
  protected $processor;

  protected function setUp() {
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
    $parameters = array();

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
    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'));
    $parameters = array();

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // 'token' should be added to the parameters array.
    $this->assertArrayHasKey('token', $parameters);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = 'test-path';
    $placeholder = hash('sha1', $path);
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
    $route = new Route('/test-path/{slug}', array(), array('_csrf_token' => 'TRUE'));
    $parameters = array('slug' => 100);

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = 'test-path/100';
    $placeholder = hash('sha1', $path);
    $placeholder_render_array = [
      '#lazy_builder' => ['route_processor_csrf:renderPlaceholderCsrfToken', [$path]],
    ];
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

  /**
   * Tests the processOutbound() method with two parameter replacements.
   */
  public function testProcessOutboundDynamicTwo() {
    $route = new Route('{slug_1}/test-path/{slug_2}', array(), array('_csrf_token' => 'TRUE'));
    $parameters = array('slug_1' => 100, 'slug_2' => 'test');

    $bubbleable_metadata = new BubbleableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $bubbleable_metadata);
    // Bubbleable metadata of routes with a _csrf_token route requirement is a
    // placeholder.
    $path = '100/test-path/test';
    $placeholder = hash('sha1', $path);
    $placeholder_render_array = [
      '#lazy_builder' => ['route_processor_csrf:renderPlaceholderCsrfToken', [$path]],
    ];
    $this->assertEquals((new BubbleableMetadata())->setAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]), $bubbleable_metadata);
  }

}
