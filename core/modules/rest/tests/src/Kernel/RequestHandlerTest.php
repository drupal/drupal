<?php

/**
 * @file
 * Contains \Drupal\Tests\rest\Kernel\RequestHandlerTest.
 */

namespace Drupal\Tests\rest\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\rest\RequestHandler;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->requestHandler = new RequestHandler();
    $this->requestHandler->setContainer($this->container);
  }

  /**
   * Assert some basic handler method logic.
   *
   * @covers ::handle
   */
  public function testBaseHandler() {
    $request = new Request();
    $route_match = new RouteMatch('test', new Route('/rest/test', ['_plugin' => 'restplugin', '_format' => 'json']));

    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);
    $resource->get(NULL, $request)
      ->shouldBeCalled();

    // Setup stub plugin manager that will return our plugin.
    $stub = $this->prophesize(ResourcePluginManager::class);
    $stub->getInstance(['id' => 'restplugin'])
      ->willReturn($resource->reveal());
    $this->container->set('plugin.manager.rest', $stub->reveal());

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
  public function testSerialization($data) {
    $request = new Request();
    $route_match = new RouteMatch('test', new Route('/rest/test', ['_plugin' => 'restplugin', '_format' => 'json']));

    $resource = $this->prophesize(StubRequestHandlerResourcePlugin::class);

    // Setup stub plugin manager that will return our plugin.
    $stub = $this->prophesize(ResourcePluginManager::class);
    $stub->getInstance(['id' => 'restplugin'])
      ->willReturn($resource->reveal());
    $this->container->set('plugin.manager.rest', $stub->reveal());

    $response = new ResourceResponse($data);
    $resource->get(NULL, $request)
      ->willReturn($response);
    $handler_response = $this->requestHandler->handle($route_match, $request);
    // Content is a serialized version of the data we provided.
    $this->assertEquals(json_encode($data), $handler_response->getContent());
  }

  public function providerTestSerialization() {
    return [
      [NULL],
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

}

class StubRequestHandlerResourcePlugin extends ResourceBase {

  /** stub methods so they can be prophesied. */
  function get() {}
  function patch() {}
}
