<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\RequestHandler;
use Drupal\rest\ResourceResponse;
use Drupal\rest\RestResourceConfigInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

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

  protected static $modules = ['serialization', 'rest'];

  /**
   * The entity storage.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $serializer = $this->prophesize(SerializerInterface::class);
    $serializer->willImplement(DecoderInterface::class);
    $serializer->decode(Json::encode(['this is an array']), 'json', Argument::type('array'))
      ->willReturn(['this is an array']);
    $this->requestHandler = new RequestHandler($serializer->reveal());
  }

  /**
   * @covers ::handle
   */
  public function testHandle(): void {
    $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], Json::encode(['this is an array']));
    $route_match = new RouteMatch('test', (new Route('/rest/test', ['_rest_resource_config' => 'rest_plugin', 'example' => ''], ['_format' => 'json']))->setMethods(['GET']));

    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);
    $resource->get('', $request)
      ->shouldBeCalled();
    $resource->getPluginDefinition()
      ->willReturn([])
      ->shouldBeCalled();

    // Setup the configuration.
    $config = $this->prophesize(RestResourceConfigInterface::class);
    $config->getResourcePlugin()->willReturn($resource->reveal());
    $config->getCacheContexts()->willReturn([]);
    $config->getCacheTags()->willReturn([]);
    $config->getCacheMaxAge()->willReturn(12);

    // Response returns NULL this time because response from plugin is not
    // a ResourceResponse so it is passed through directly.
    $response = $this->requestHandler->handle($route_match, $request, $config->reveal());
    $this->assertEquals(NULL, $response);

    // Response will return a ResourceResponse this time.
    $response = new ResourceResponse([]);
    $resource->get(NULL, $request)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request, $config->reveal());
    $this->assertEquals($response, $handler_response);

    // We will call the patch method this time.
    $route_match = new RouteMatch('test', (new Route('/rest/test', ['_rest_resource_config' => 'rest_plugin', 'example_original' => ''], ['_content_type_format' => 'json']))->setMethods(['PATCH']));
    $request->setMethod('PATCH');
    $response = new ResourceResponse([]);
    $resource->patch(['this is an array'], $request)
      ->shouldBeCalledTimes(1)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request, $config->reveal());
    $this->assertEquals($response, $handler_response);
  }

}

/**
 * Stub class where we can prophesize methods.
 */
class StubRequestHandlerResourcePlugin extends ResourceBase {

  public function get($example = NULL, ?Request $request = NULL) {}

  public function post() {}

  public function patch($data, Request $request) {}

  public function delete() {}

}
