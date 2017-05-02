<?php

namespace Drupal\Tests\rest\Unit\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rest\EventSubscriber\ResourceResponseSubscriber;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ResourceResponseInterface;
use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\serialization\Encoder\XmlEncoder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 * @group rest
 */
class ResourceResponseSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onResponse
   * @dataProvider providerTestSerialization
   */
  public function testSerialization($data, $expected_response = FALSE) {
    $request = new Request();
    $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => 'restplugin'], ['_format' => 'json']));

    $handler_response = new ResourceResponse($data);
    $resource_response_subscriber = $this->getFunctioningResourceResponseSubscriber($route_match);
    $event = new FilterResponseEvent(
      $this->prophesize(HttpKernelInterface::class)->reveal(),
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      $handler_response
    );
    $resource_response_subscriber->onResponse($event);

    // Content is a serialized version of the data we provided.
    $this->assertEquals($expected_response !== FALSE ? $expected_response : Json::encode($data), $event->getResponse()->getContent());
  }

  public function providerTestSerialization() {
    return [
      // The default data for \Drupal\rest\ResourceResponse.
      'default' => [NULL, ''],
      'empty string' => [''],
      'simple string' => ['string'],
      'complex string' => ['Complex \ string $%^&@ with unicode ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΣὨ'],
      'empty array' => [[]],
      'numeric array' => [['test']],
      'associative array' => [['test' => 'foobar']],
      'boolean true' => [TRUE],
      'boolean false' => [FALSE],
      // @todo Not supported. https://www.drupal.org/node/2427811
      // [new \stdClass()],
      // [(object) ['test' => 'foobar']],
    ];
  }

  /**
   * @covers ::getResponseFormat
   *
   * Note this does *not* need to test formats being requested that are not
   * accepted by the server, because the routing system would have already
   * prevented those from reaching the controller.
   *
   * @dataProvider providerTestResponseFormat
   */
  public function testResponseFormat($methods, array $supported_formats, $request_format, array $request_headers, $request_body, $expected_response_format, $expected_response_content_type, $expected_response_content) {
    foreach ($request_headers as $key => $value) {
      unset($request_headers[$key]);
      $key = strtoupper(str_replace('-', '_', $key));
      $request_headers[$key] = $value;
    }

    foreach ($methods as $method) {
      $request = Request::create('/rest/test', $method, [], [], [], $request_headers, $request_body);
      // \Drupal\Core\StackMiddleware\NegotiationMiddleware normally takes care
      // of this so we'll hard code it here.
      if ($request_format) {
        $request->setRequestFormat($request_format);
      }
      $route_requirement_key_format = $request->isMethodCacheable() ? '_format' : '_content_type_format';
      $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => $this->randomMachineName()], [$route_requirement_key_format => implode('|', $supported_formats)]));

      $resource_response_subscriber = new ResourceResponseSubscriber(
        $this->prophesize(SerializerInterface::class)->reveal(),
        $this->prophesize(RendererInterface::class)->reveal(),
        $route_match
      );

      $this->assertSame($expected_response_format, $resource_response_subscriber->getResponseFormat($route_match, $request));
    }
  }

  /**
   * @covers ::onResponse
   * @covers ::getResponseFormat
   * @covers ::renderResponseBody
   * @covers ::flattenResponse
   *
   * @dataProvider providerTestResponseFormat
   */
  public function testOnResponseWithCacheableResponse($methods, array $supported_formats, $request_format, array $request_headers, $request_body, $expected_response_format, $expected_response_content_type, $expected_response_content) {
    $rest_config_name = $this->randomMachineName();

    foreach ($request_headers as $key => $value) {
      unset($request_headers[$key]);
      $key = strtoupper(str_replace('-', '_', $key));
      $request_headers[$key] = $value;
    }

    foreach ($methods as $method) {
      $request = Request::create('/rest/test', $method, [], [], [], $request_headers, $request_body);
      // \Drupal\Core\StackMiddleware\NegotiationMiddleware normally takes care
      // of this so we'll hard code it here.
      if ($request_format) {
        $request->setRequestFormat($request_format);
      }
      $route_requirement_key_format = $request->isMethodCacheable() ? '_format' : '_content_type_format';
      $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => $rest_config_name], [$route_requirement_key_format => implode('|', $supported_formats)]));

      // The RequestHandler must return a ResourceResponseInterface object.
      $handler_response = new ResourceResponse($method !== 'DELETE' ? ['REST' => 'Drupal'] : NULL);
      $this->assertInstanceOf(ResourceResponseInterface::class, $handler_response);
      $this->assertInstanceOf(CacheableResponseInterface::class, $handler_response);

      // The ResourceResponseSubscriber must then generate a response body and
      // transform it to a plain CacheableResponse object.
      $resource_response_subscriber = $this->getFunctioningResourceResponseSubscriber($route_match);
      $event = new FilterResponseEvent(
        $this->prophesize(HttpKernelInterface::class)->reveal(),
        $request,
        HttpKernelInterface::MASTER_REQUEST,
        $handler_response
      );
      $resource_response_subscriber->onResponse($event);
      $final_response = $event->getResponse();
      $this->assertNotInstanceOf(ResourceResponseInterface::class, $final_response);
      $this->assertInstanceOf(CacheableResponseInterface::class, $final_response);
      $this->assertSame($expected_response_content_type, $final_response->headers->get('Content-Type'));
      $this->assertEquals($expected_response_content, $final_response->getContent());
    }
  }

  /**
   * @covers ::onResponse
   * @covers ::getResponseFormat
   * @covers ::renderResponseBody
   * @covers ::flattenResponse
   *
   * @dataProvider providerTestResponseFormat
   */
  public function testOnResponseWithUncacheableResponse($methods, array $supported_formats, $request_format, array $request_headers, $request_body, $expected_response_format, $expected_response_content_type, $expected_response_content) {
    $rest_config_name = $this->randomMachineName();

    foreach ($request_headers as $key => $value) {
      unset($request_headers[$key]);
      $key = strtoupper(str_replace('-', '_', $key));
      $request_headers[$key] = $value;
    }

    foreach ($methods as $method) {
      $request = Request::create('/rest/test', $method, [], [], [], $request_headers, $request_body);
      // \Drupal\Core\StackMiddleware\NegotiationMiddleware normally takes care
      // of this so we'll hard code it here.
      if ($request_format) {
        $request->setRequestFormat($request_format);
      }
      $route_requirement_key_format = $request->isMethodCacheable() ? '_format' : '_content_type_format';
      $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => $rest_config_name], [$route_requirement_key_format => implode('|', $supported_formats)]));

      // The RequestHandler must return a ResourceResponseInterface object.
      $handler_response = new ModifiedResourceResponse($method !== 'DELETE' ? ['REST' => 'Drupal'] : NULL);
      $this->assertInstanceOf(ResourceResponseInterface::class, $handler_response);
      $this->assertNotInstanceOf(CacheableResponseInterface::class, $handler_response);

      // The ResourceResponseSubscriber must then generate a response body and
      // transform it to a plain Response object.
      $resource_response_subscriber = $this->getFunctioningResourceResponseSubscriber($route_match);
      $event = new FilterResponseEvent(
        $this->prophesize(HttpKernelInterface::class)->reveal(),
        $request,
        HttpKernelInterface::MASTER_REQUEST,
        $handler_response
      );
      $resource_response_subscriber->onResponse($event);
      $final_response = $event->getResponse();
      $this->assertNotInstanceOf(ResourceResponseInterface::class, $final_response);
      $this->assertNotInstanceOf(CacheableResponseInterface::class, $final_response);
      $this->assertSame($expected_response_content_type, $final_response->headers->get('Content-Type'));
      $this->assertEquals($expected_response_content, $final_response->getContent());
    }
  }

  /**
   * @return array
   *   0. methods to test
   *   1. supported formats for route requirements
   *   2. request format
   *   3. request headers
   *   4. request body
   *   5. expected response format
   *   6. expected response content type
   *   7. expected response body
   */
  public function providerTestResponseFormat() {
    $json_encoded = Json::encode(['REST' => 'Drupal']);
    $xml_encoded = "<?xml version=\"1.0\"?>\n<response><REST>Drupal</REST></response>\n";

    $safe_method_test_cases = [
      'safe methods: client requested format (JSON)' => [
        // @todo add 'HEAD' in https://www.drupal.org/node/2752325
        ['GET'],
        ['xml', 'json'],
        'json',
        [],
        NULL,
        'json',
        'application/json',
        $json_encoded,
      ],
      'safe methods: client requested format (XML)' => [
        // @todo add 'HEAD' in https://www.drupal.org/node/2752325
        ['GET'],
        ['xml', 'json'],
        'xml',
        [],
        NULL,
        'xml',
        'text/xml',
        $xml_encoded,
      ],
      'safe methods: client requested no format: response should use the first configured format (JSON)' => [
        // @todo add 'HEAD' in https://www.drupal.org/node/2752325
        ['GET'],
        ['json', 'xml'],
        FALSE,
        [],
        NULL,
        'json',
        'application/json',
        $json_encoded,
      ],
      'safe methods: client requested no format: response should use the first configured format (XML)' => [
        // @todo add 'HEAD' in https://www.drupal.org/node/2752325
        ['GET'],
        ['xml', 'json'],
        FALSE,
        [],
        NULL,
        'xml',
        'text/xml',
        $xml_encoded,
      ],
    ];

    $unsafe_method_bodied_test_cases = [
      'unsafe methods with response (POST, PATCH): client requested no format, response should use request body format (JSON)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        FALSE,
        ['Content-Type' => 'application/json'],
        $json_encoded,
        'json',
        'application/json',
        $json_encoded,
      ],
      'unsafe methods with response (POST, PATCH): client requested no format, response should use request body format (XML)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        FALSE,
        ['Content-Type' => 'text/xml'],
        $xml_encoded,
        'xml',
        'text/xml',
        $xml_encoded,
      ],
      'unsafe methods with response (POST, PATCH): client requested format other than request body format (JSON): response format should use requested format (XML)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        'xml',
        ['Content-Type' => 'application/json'],
        $json_encoded,
        'xml',
        'text/xml',
        $xml_encoded,
      ],
      'unsafe methods with response (POST, PATCH): client requested format other than request body format (XML), but is allowed for the request body (JSON)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        'json',
        ['Content-Type' => 'text/xml'],
        $xml_encoded,
        'json',
        'application/json',
        $json_encoded,
      ],
    ];

    $unsafe_method_bodyless_test_cases = [
      'unsafe methods with response bodies (DELETE): client requested no format, response should have no format' => [
        ['DELETE'],
        ['xml', 'json'],
        FALSE,
        ['Content-Type' => 'application/json'],
        NULL,
        'xml',
        NULL,
        '',
      ],
      'unsafe methods with response bodies (DELETE): client requested format (XML), response should have no format' => [
        ['DELETE'],
        ['xml', 'json'],
        'xml',
        ['Content-Type' => 'application/json'],
        NULL,
        'xml',
        NULL,
        '',
      ],
      'unsafe methods with response bodies (DELETE): client requested format (JSON), response should have no format' => [
        ['DELETE'],
        ['xml', 'json'],
        'json',
        ['Content-Type' => 'application/json'],
        NULL,
        'json',
        NULL,
        '',
      ],
    ];

    return $safe_method_test_cases + $unsafe_method_bodied_test_cases + $unsafe_method_bodyless_test_cases;
  }

  /**
   * @return \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
   */
  protected function getFunctioningResourceResponseSubscriber(RouteMatchInterface $route_match) {
    // Create a dummy of the renderer service.
    $renderer = $this->prophesize(RendererInterface::class);
    $renderer->executeInRenderContext(Argument::type(RenderContext::class), Argument::type('callable'))
      ->will(function ($args) {
        $callable = $args[1];
        return $callable();
      });

    // Instantiate the ResourceResponseSubscriber we will test.
    $resource_response_subscriber = new ResourceResponseSubscriber(
      new Serializer([], [new JsonEncoder(), new XmlEncoder()]),
      $renderer->reveal(),
      $route_match
    );

    return $resource_response_subscriber;
  }

}
