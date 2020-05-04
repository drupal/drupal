<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\ControllerResolverTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\ArgumentResolver\RawParameterValueResolver;
use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @coversDefaultClass \Drupal\Core\Controller\ControllerResolver
 * @group Controller
 */
class ControllerResolverTest extends UnitTestCase {

  /**
   * The tested controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolver
   */
  public $controllerResolver;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The PSR-7 converter.
   *
   * @var \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface
   */
  protected $httpMessageFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $class_resolver = new ClassResolver();
    $class_resolver->setContainer($this->container);
    $this->httpMessageFactory = new DiactorosFactory();
    $this->controllerResolver = new ControllerResolver($this->httpMessageFactory, $class_resolver);
  }

  /**
   * Tests getArguments().
   *
   * Ensure that doGetArguments uses converted arguments if available.
   *
   * @see \Drupal\Core\Controller\ControllerResolver::getArguments()
   * @see \Drupal\Core\Controller\ControllerResolver::doGetArguments()
   *
   * @group legacy
   * @expectedDeprecation Drupal\Core\Controller\ControllerResolver::doGetArguments is deprecated as of 8.6.0 and will be removed in 9.0. Inject the "http_kernel.controller.argument_resolver" service instead.
   */
  public function testGetArguments() {
    if (!in_array('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface', class_implements('Symfony\Component\HttpKernel\Controller\ControllerResolver'))) {
      $this->markTestSkipped("Do not test ::getArguments() method when it is not implemented by Symfony's ControllerResolver.");
    }
    $controller = function (EntityInterface $entity, $user, RouteMatchInterface $route_match, ServerRequestInterface $psr_7) {
    };
    $mock_entity = $this->getMockBuilder('Drupal\Core\Entity\EntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    $mock_account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $request = new Request([], [], [
      'entity' => $mock_entity,
      'user' => $mock_account,
      '_raw_variables' => new ParameterBag(['entity' => 1, 'user' => 1]),
    ], [], [], ['HTTP_HOST' => 'drupal.org']);
    $arguments = $this->controllerResolver->getArguments($request, $controller);

    $this->assertEquals($mock_entity, $arguments[0]);
    $this->assertEquals($mock_account, $arguments[1]);
    $this->assertEquals(RouteMatch::createFromRequest($request), $arguments[2], 'Ensure that the route match object is passed along as well');
    $this->assertInstanceOf(ServerRequestInterface::class, $arguments[3], 'Ensure that the PSR-7 object is passed along as well');
  }

  /**
   * Tests createController().
   *
   * @dataProvider providerTestCreateController
   */
  public function testCreateController($controller, $class, $output) {
    $this->container->set('some_service', new MockController());
    $result = $this->controllerResolver->getControllerFromDefinition($controller);
    $this->assertCallableController($result, $class, $output);
  }

  /**
   * Provides test data for testCreateController().
   */
  public function providerTestCreateController() {
    return [
      // Tests class::method.
      ['Drupal\Tests\Core\Controller\MockController::getResult', 'Drupal\Tests\Core\Controller\MockController', 'This is a regular controller.'],
      // Tests service:method.
      ['some_service:getResult', 'Drupal\Tests\Core\Controller\MockController', 'This is a regular controller.'],
      // Tests a class with injection.
      ['Drupal\Tests\Core\Controller\MockContainerInjection::getResult', 'Drupal\Tests\Core\Controller\MockContainerInjection', 'This used injection.'],
      // Tests a ContainerAware class.
      ['Drupal\Tests\Core\Controller\MockContainerAware::getResult', 'Drupal\Tests\Core\Controller\MockContainerAware', 'This is container aware.'],
    ];
  }

  /**
   * Tests createController() with a non-existent class.
   */
  public function testCreateControllerNonExistentClass() {
    $this->expectException(\InvalidArgumentException::class);
    $this->controllerResolver->getControllerFromDefinition('Class::method');
  }

  /**
   * Tests createController() with an invalid name.
   */
  public function testCreateControllerInvalidName() {
    $this->expectException(\LogicException::class);
    $this->controllerResolver->getControllerFromDefinition('ClassWithoutMethod');
  }

  /**
   * Tests getController().
   *
   * @dataProvider providerTestGetController
   */
  public function testGetController($attributes, $class, $output = NULL) {
    $request = new Request([], [], $attributes);
    $result = $this->controllerResolver->getController($request);
    if ($class) {
      $this->assertCallableController($result, $class, $output);
    }
    else {
      $this->assertSame(FALSE, $result);
    }
  }

  /**
   * Provides test data for testGetController().
   */
  public function providerTestGetController() {
    return [
      // Tests passing a controller via the request.
      [['_controller' => 'Drupal\Tests\Core\Controller\MockContainerAware::getResult'], 'Drupal\Tests\Core\Controller\MockContainerAware', 'This is container aware.'],
      // Tests a request with no controller specified.
      [[], FALSE],
    ];
  }

  /**
   * Tests getControllerFromDefinition().
   *
   * @dataProvider providerTestGetControllerFromDefinition
   */
  public function testGetControllerFromDefinition($definition, $output) {
    $this->container->set('invoke_service', new MockInvokeController());
    $controller = $this->controllerResolver->getControllerFromDefinition($definition);
    $this->assertCallableController($controller, NULL, $output);
  }

  /**
   * Provides test data for testGetControllerFromDefinition().
   */
  public function providerTestGetControllerFromDefinition() {
    return [
      // Tests a method on an object.
      [[new MockController(), 'getResult'], 'This is a regular controller.'],
      // Tests a function.
      ['phpversion', phpversion()],
      // Tests an object using __invoke().
      [new MockInvokeController(), 'This used __invoke().'],
      // Tests a class using __invoke().
      ['Drupal\Tests\Core\Controller\MockInvokeController', 'This used __invoke().'],
      // Tests a service from the container using __invoke().
      ['invoke_service', 'This used __invoke().'],
    ];
  }

  /**
   * Tests getControllerFromDefinition() without a callable.
   */
  public function testGetControllerFromDefinitionNotCallable() {
    $this->expectException(\InvalidArgumentException::class);
    $this->controllerResolver->getControllerFromDefinition('Drupal\Tests\Core\Controller\MockController::bananas');
  }

  /**
   * Asserts that the controller is callable and produces the correct output.
   *
   * @param callable $controller
   *   A callable controller.
   * @param string|null $class
   *   Either the name of the class the controller represents, or NULL if it is
   *   not an object.
   * @param mixed $output
   *   The output expected for this controller.
   */
  protected function assertCallableController($controller, $class, $output) {
    if ($class) {
      $this->assertIsObject($controller[0]);
      $this->assertInstanceOf($class, $controller[0]);
    }
    $this->assertIsCallable($controller);
    $this->assertSame($output, call_user_func($controller));
  }

  /**
   * Tests getArguments with a route match and a request.
   *
   * @covers ::doGetArguments
   *
   * @group legacy
   */
  public function testGetArgumentsWithRouteMatchAndRequest() {
    if (!in_array('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface', class_implements('Symfony\Component\HttpKernel\Controller\ControllerResolver'))) {
      $this->markTestSkipped("Do not test ::getArguments() method when it is not implemented by Symfony's ControllerResolver.");
    }
    $request = Request::create('/test');
    $mock_controller = new MockController();
    $arguments = $this->controllerResolver->getArguments($request, [$mock_controller, 'getControllerWithRequestAndRouteMatch']);
    $this->assertEquals([RouteMatch::createFromRequest($request), $request], $arguments);
  }

  /**
   * Tests getArguments with a route match and a PSR-7 request.
   *
   * @covers ::doGetArguments
   *
   * @group legacy
   */
  public function testGetArgumentsWithRouteMatchAndPsr7Request() {
    if (!in_array('Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface', class_implements('Symfony\Component\HttpKernel\Controller\ControllerResolver'))) {
      $this->markTestSkipped("Do not test ::getArguments() method when it is not implemented by Symfony's ControllerResolver.");
    }
    $request = Request::create('/test');
    $mock_controller = new MockControllerPsr7();
    $arguments = $this->controllerResolver->getArguments($request, [$mock_controller, 'getControllerWithRequestAndRouteMatch']);
    $this->assertEquals(RouteMatch::createFromRequest($request), $arguments[0], 'Ensure that the route match object is passed along as well');
    $this->assertInstanceOf('Psr\Http\Message\ServerRequestInterface', $arguments[1], 'Ensure that the PSR-7 object is passed along as well');
  }

  /**
   * @group legacy
   * @expectedDeprecation Drupal\Core\Controller\ArgumentResolver\RawParameterValueResolver is deprecated in Drupal 8.8.1 and will be removed before Drupal 9.0.0. This class exists to prevent problems with updating core using Drush 8. There is no replacement.
   */
  public function testRawParameterValueResolver() {
    $resolver = new RawParameterValueResolver();
    $metadata = $this->prophesize(ArgumentMetadata::class);
    $this->assertFalse($resolver->supports(Request::create('/test'), $metadata->reveal()));
  }

}

class MockController {

  public function getResult() {
    return 'This is a regular controller.';
  }

  public function getControllerWithRequestAndRouteMatch(RouteMatchInterface $route_match, Request $request) {
    return 'this is another example controller';
  }

}
class MockControllerPsr7 {

  public function getResult() {
    return ['#markup' => 'This is a regular controller'];
  }

  public function getControllerWithRequestAndRouteMatch(RouteMatchInterface $route_match, ServerRequestInterface $request) {
    return ['#markup' => 'this is another example controller'];
  }

}

class MockContainerInjection implements ContainerInjectionInterface {
  protected $result;

  public function __construct($result) {
    $this->result = $result;
  }

  public static function create(ContainerInterface $container) {
    return new static('This used injection.');
  }

  public function getResult() {
    return $this->result;
  }

}
class MockContainerAware implements ContainerAwareInterface {
  use ContainerAwareTrait;

  public function getResult() {
    return 'This is container aware.';
  }

}
class MockInvokeController {

  public function __invoke() {
    return 'This used __invoke().';
  }

}
