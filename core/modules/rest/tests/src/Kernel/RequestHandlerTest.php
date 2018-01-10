<?php

namespace Drupal\Tests\rest\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\RequestHandler;
use Drupal\rest\ResourceResponse;
use Drupal\rest\RestResourceConfigInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
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
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('rest.settings')
      ->willReturn($this->prophesize(ImmutableConfig::class)->reveal());
    $serializer = $this->prophesize(SerializerInterface::class);
    $this->requestHandler = new RequestHandler($this->entityStorage->reveal(), $config_factory->reveal(), $serializer->reveal());
  }

  /**
   * @covers ::handle
   */
  public function testHandle() {
    $request = new Request();
    $route_match = new RouteMatch('test', (new Route('/rest/test', ['_rest_resource_config' => 'restplugin'], ['_format' => 'json']))->setMethods(['GET']));

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
    $route_match = new RouteMatch('test', (new Route('/rest/test', ['_rest_resource_config' => 'restplugin'], ['_content_type_format' => 'json']))->setMethods(['PATCH']));
    $request->setMethod('PATCH');
    $response = new ResourceResponse([]);
    $resource->patch(NULL, $request)
      ->shouldBeCalledTimes(1)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request);
    $this->assertEquals($response, $handler_response);
  }

}

/**
 * Stub class where we can prophesize methods.
 */
class StubRequestHandlerResourcePlugin extends ResourceBase {

  public function get($example, Request $request) {}
  public function post() {}
  public function patch($example_original, Request $request) {}
  public function delete() {}

}
