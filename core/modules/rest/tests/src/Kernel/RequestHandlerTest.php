<?php

namespace Drupal\Tests\rest\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\RequestHandler;
use Drupal\rest\ResourceResponse;
use Drupal\rest\RestResourceConfigInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Test REST RequestHandler controller logic.
 *
 * @group rest
 * @coversDefaultClass \Drupal\rest\RequestHandler
 */
class RequestHandlerTest extends KernelTestBase {

  /**
   * @var \Drupal\rest\RequestHandler
   */
  protected $requestHandler;

  public static $modules = ['serialization', 'rest'];

  /**
   * The entity storage.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->requestHandler = new RequestHandler($this->entityStorage->reveal());
    $this->requestHandler->setContainer($this->container);
  }

  /**
   * Assert some basic handler method logic.
   *
   * @covers ::handle
   */
  public function testBaseHandler() {
    $request = new Request();
    $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => 'restplugin'], ['_format' => 'json']));

    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);
    $resource->get(NULL, $request)
      ->shouldBeCalled();

    // Setup the configuration.
    $config = $this->prophesize(RestResourceConfigInterface::class);
    $config->getResourcePlugin()->willReturn($resource->reveal());
    $config->getCacheContexts()->willReturn([]);
    $config->getCacheTags()->willReturn([]);
    $config->getCacheMaxAge()->willReturn(12);
    $this->entityStorage->load('restplugin')->willReturn($config->reveal());

    // Response returns NULL this time because response from plugin is not
    // a ResourceResponse so it is passed through directly.
    $response = $this->requestHandler->handle($route_match, $request);
    $this->assertEquals(NULL, $response);

    // Response will return a ResourceResponse this time.
    $response = new ResourceResponse([]);
    $resource->get(NULL, $request)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request);
    $this->assertEquals($response, $handler_response);

    // We will call the patch method this time.
    $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => 'restplugin'], ['_content_type_format' => 'json']));
    $request->setMethod('PATCH');
    $response = new ResourceResponse([]);
    $resource->patch(NULL, $request)
      ->shouldBeCalledTimes(1)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request);
    $this->assertEquals($response, $handler_response);
  }

  /**
   * Test that given structured data, the request handler will serialize it.
   *
   * @dataProvider providerTestSerialization
   * @covers ::handle
   */
  public function testSerialization($data, $expected_response = FALSE) {
    $request = new Request();
    $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => 'restplugin'], ['_format' => 'json']));

    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);

    // Mock the configuration.
    $config = $this->prophesize(RestResourceConfigInterface::class);
    $config->getResourcePlugin()->willReturn($resource->reveal());
    $config->getCacheContexts()->willReturn([]);
    $config->getCacheTags()->willReturn([]);
    $config->getCacheMaxAge()->willReturn(12);
    $this->entityStorage->load('restplugin')->willReturn($config->reveal());

    $response = new ResourceResponse($data);
    $resource->get(NULL, $request)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request);
    // Content is a serialized version of the data we provided.
    $this->assertEquals($expected_response !== FALSE ? $expected_response : json_encode($data), $handler_response->getContent());
  }

  public function providerTestSerialization() {
    return [
      // The default data for \Drupal\rest\ResourceResponse.
      [NULL, ''],
      [''],
      ['string'],
      ['Complex \ string $%^&@ with unicode ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΣὨ'],
      [[]],
      [['test']],
      [['test' => 'foobar']],
      [TRUE],
      [FALSE],
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
   * prevented those from reaching RequestHandler.
   *
   * @param string[] $methods
   *   The HTTP methods to test.
   * @param string[] $supported_formats
   *   The supported formats for the REST route to be tested.
   * @param string|false $request_format
   *   The value for the ?_format URL query argument, if any.
   * @param string[] $request_headers
   *   The request headers to send, if any.
   * @param string|null $request_body
   *   The request body to send, if any.
   * @param string|null $expected_response_content_type
   *   The expected MIME type of the response, if any.
   * @param string $expected_response_content
   *   The expected content of the response.
   *
   * @dataProvider providerTestResponseFormat
   */
  public function testResponseFormat($methods, array $supported_formats, $request_format, array $request_headers, $request_body, $expected_response_content_type, $expected_response_content) {
    $rest_config_name = $this->randomMachineName();

    $parameters = [];
    if ($request_format !== FALSE) {
      $parameters['_format'] = $request_format;
    }

    foreach ($request_headers as $key => $value) {
      unset($request_headers[$key]);
      $key = strtoupper(str_replace('-', '_', $key));
      $request_headers[$key] = $value;
    }

    foreach ($methods as $method) {
      $request = Request::create('/rest/test', $method, $parameters, [], [], $request_headers, $request_body);
      $route_requirement_key_format = $request->isMethodSafe() ? '_format' : '_content_type_format';
      $route_match = new RouteMatch('test', new Route('/rest/test', ['_rest_resource_config' => $rest_config_name], [$route_requirement_key_format => implode('|', $supported_formats)]));

      $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);

      // Mock the configuration.
      $config = $this->prophesize(RestResourceConfigInterface::class);
      $config->getFormats($method)->willReturn($supported_formats);
      $config->getResourcePlugin()->willReturn($resource->reveal());
      $config->getCacheContexts()->willReturn([]);
      $config->getCacheTags()->willReturn([]);
      $config->getCacheMaxAge()->willReturn(12);
      $this->entityStorage->load($rest_config_name)->willReturn($config->reveal());

      // Mock the resource plugin.
      $response = new ResourceResponse($method !== 'DELETE' ? ['REST' => 'Drupal'] : NULL);
      $resource->getPluginDefinition()->willReturn([]);
      $method_prophecy = new MethodProphecy($resource, strtolower($method), [Argument::any(), $request]);
      $method_prophecy->willReturn($response);
      $resource->addMethodProphecy($method_prophecy);

      // Test the request handler.
      $handler_response = $this->requestHandler->handle($route_match, $request);
      $this->assertSame($expected_response_content_type, $handler_response->headers->get('Content-Type'));
      $this->assertEquals($expected_response_content, $handler_response->getContent());
    }
  }

  /**
   * @return array
   *   0. methods to test
   *   1. supported formats for route requirements
   *   2. request format
   *   3. request headers
   *   4. request body
   *   5. expected response content type
   *   6. expected response body
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
        'application/json',
        $json_encoded,
      ],
      'unsafe methods with response (POST, PATCH): client requested no format, response should use request body format (XML)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        FALSE,
        ['Content-Type' => 'text/xml'],
        $xml_encoded,
        'text/xml',
        $xml_encoded,
      ],
      'unsafe methods with response (POST, PATCH): client requested format other than request body format (JSON): response format should use requested format (XML)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        'xml',
        ['Content-Type' => 'application/json'],
        $json_encoded,
        'text/xml',
        $xml_encoded,
      ],
      'unsafe methods with response (POST, PATCH): client requested format other than request body format (XML), but is allowed for the request body (JSON)' => [
        ['POST', 'PATCH'],
        ['xml', 'json'],
        'json',
        ['Content-Type' => 'text/xml'],
        $xml_encoded,
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
        $json_encoded,
        NULL,
        '',
      ],
      'unsafe methods with response bodies (DELETE): client requested format (XML), response should have no format' => [
        ['DELETE'],
        ['xml', 'json'],
        'xml',
        ['Content-Type' => 'application/json'],
        $json_encoded,
        NULL,
        '',
      ],
      'unsafe methods with response bodies (DELETE): client requested format (JSON), response should have no format' => [
        ['DELETE'],
        ['xml', 'json'],
        'json',
        ['Content-Type' => 'application/json'],
        $json_encoded,
        NULL,
        '',
      ],
    ];

    return $safe_method_test_cases + $unsafe_method_bodied_test_cases + $unsafe_method_bodyless_test_cases;
  }

}

/**
 * Stub class where we can prophesize methods.
 */
class StubRequestHandlerResourcePlugin extends ResourceBase {

  function get() {}
  function post() {}
  function patch() {}
  function delete() {}

}
