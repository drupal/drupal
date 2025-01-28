<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\RouteProcessorCsrf;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\RouteProcessorCsrf
 * @group Access
 */
class RouteProcessorCsrfTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   */
  protected CsrfTokenGenerator&MockObject $csrfToken;

  /**
   * The mock request stack.
   */
  protected RequestStack&MockObject $requestStack;

  /**
   * The route processor.
   */
  protected RouteProcessorCsrf $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();

    $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->disableOriginalConstructor()
      ->getMock();

    $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
    $request->expects($this->any())
      ->method('getRequestFormat')
      ->willReturn('html');

    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->processor = new RouteProcessorCsrf($this->csrfToken, $this->requestStack);
  }

  /**
   * Tests the processOutbound() method with no _csrf_token route requirement.
   */
  public function testProcessOutboundNoRequirement(): void {
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
  public function testProcessOutbound(): void {
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
  public function testProcessOutboundDynamicOne(): void {
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
  public function testProcessOutboundDynamicTwo(): void {
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

  /**
   * Tests JSON requests to get no placeholders, but real tokens.
   */
  public function testProcessOutboundJsonFormat(): void {
    // Create a new request mock that returns 'json' format.
    $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
    $request->expects($this->once())
      ->method('getRequestFormat')
      ->willReturn('json');
    $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
    $this->requestStack->expects($this->once())
      ->method('getCurrentRequest')
      ->willReturn($request);

    // Mock that the CSRF token service should be called once with 'test-path'
    // and return a test token.
    $this->csrfToken->expects($this->any())
      ->method('get')
      ->with('test-path')
      ->willReturn('real_token_value');

    $this->processor = new RouteProcessorCsrf($this->csrfToken, $this->requestStack);

    $route = new Route('/test-path', [], ['_csrf_token' => 'TRUE']);
    $parameters = [];
    // For JSON requests, the actual CSRF token should be in parameters,
    // regardless of whether cache metadata is present.
    $this->processor->processOutbound('test', $route, $parameters);
    $this->assertEquals('real_token_value', $parameters['token']);
    $this->processor->processOutbound('test', $route, $parameters, new BubbleableMetadata());
    $this->assertEquals('real_token_value', $parameters['token']);
  }

}
