<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $class_resolver = new ClassResolver();
    $class_resolver->setContainer($this->container);
    $callable_resolver = new CallableResolver($class_resolver);
    $this->controllerResolver = new ControllerResolver($callable_resolver);
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
      $this->assertFalse($result);
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
   * @param string|null $output
   *   The output expected for this controller.
   *
   * @internal
   */
  protected function assertCallableController(callable $controller, ?string $class, ?string $output): void {
    if ($class) {
      $this->assertIsObject($controller[0]);
      $this->assertInstanceOf($class, $controller[0]);
    }
    $this->assertIsCallable($controller);
    $this->assertSame($output, call_user_func($controller));
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
