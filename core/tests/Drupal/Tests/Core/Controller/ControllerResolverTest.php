<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\ControllerResolverTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $class_resolver = new ClassResolver();
    $class_resolver->setContainer($this->container);
    $this->controllerResolver = new ControllerResolver($class_resolver);
  }

  /**
   * Tests getArguments().
   *
   * Ensure that doGetArguments uses converted arguments if available.
   *
   * @see \Drupal\Core\Controller\ControllerResolver::getArguments()
   * @see \Drupal\Core\Controller\ControllerResolver::doGetArguments()
   */
  public function testGetArguments() {
    $controller = function(EntityInterface $entity, $user) {
    };
    $mock_entity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $mock_account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $request = new Request(array(), array(), array(
      'entity' => $mock_entity,
      'user' => $mock_account,
      '_raw_variables' => new ParameterBag(array('entity' => 1, 'user' => 1)),
    ));
    $arguments = $this->controllerResolver->getArguments($request, $controller);

    $this->assertEquals($mock_entity, $arguments[0], 'Type hinted variables should use upcasted values.');
    $this->assertEquals(1, $arguments[1], 'Not type hinted variables should use not upcasted values.');
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
    return array(
      // Tests class::method.
      array('Drupal\Tests\Core\Controller\MockController::getResult', 'Drupal\Tests\Core\Controller\MockController', 'This is a regular controller.'),
      // Tests service:method.
      array('some_service:getResult', 'Drupal\Tests\Core\Controller\MockController', 'This is a regular controller.'),
      // Tests a class with injection.
      array('Drupal\Tests\Core\Controller\MockContainerInjection::getResult', 'Drupal\Tests\Core\Controller\MockContainerInjection', 'This used injection.'),
      // Tests a ContainerAware class.
      array('Drupal\Tests\Core\Controller\MockContainerAware::getResult', 'Drupal\Tests\Core\Controller\MockContainerAware', 'This is container aware.'),
    );
  }

  /**
   * Tests createController() with a non-existent class.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCreateControllerNonExistentClass() {
    $this->controllerResolver->getControllerFromDefinition('Class::method');
  }

  /**
   * Tests createController() with an invalid name.
   *
   * @expectedException \LogicException
   */
  public function testCreateControllerInvalidName() {
    $this->controllerResolver->getControllerFromDefinition('ClassWithoutMethod');
  }

  /**
   * Tests getController().
   *
   * @dataProvider providerTestGetController
   */
  public function testGetController($attributes, $class, $output = NULL) {
    $request = new Request(array(), array(), $attributes);
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
    return array(
      // Tests passing a controller via the request.
      array(array('_controller' => 'Drupal\Tests\Core\Controller\MockContainerAware::getResult'), 'Drupal\Tests\Core\Controller\MockContainerAware', 'This is container aware.'),
      // Tests a request with no controller specified.
      array(array(), FALSE)
    );
  }

  /**
   * Tests getControllerFromDefinition().
   *
   * @dataProvider providerTestGetControllerFromDefinition
   */
  public function testGetControllerFromDefinition($definition, $output) {
    $controller = $this->controllerResolver->getControllerFromDefinition($definition);
    $this->assertCallableController($controller, NULL, $output);
  }

  /**
   * Provides test data for testGetControllerFromDefinition().
   */
  public function providerTestGetControllerFromDefinition() {
    return array(
      // Tests a method on an object.
      array(array(new MockController(), 'getResult'), 'This is a regular controller.'),
      // Tests a function.
      array('phpversion', phpversion()),
      // Tests an object using __invoke().
      array(new MockInvokeController(), 'This used __invoke().'),
      // Tests a class using __invoke().
      array('Drupal\Tests\Core\Controller\MockInvokeController', 'This used __invoke().'),
    );
  }
  /**
   * Tests getControllerFromDefinition() without a callable.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetControllerFromDefinitionNotCallable() {
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
      $this->assertTrue(is_object($controller[0]));
      $this->assertInstanceOf($class, $controller[0]);
    }
    $this->assertTrue(is_callable($controller));
    $this->assertSame($output, call_user_func($controller));
  }

}

class MockController {
  public function getResult() {
    return 'This is a regular controller.';
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
class MockContainerAware extends ContainerAware {
  public function getResult() {
    return 'This is container aware.';
  }
}
class MockInvokeController {
  public function __invoke() {
    return 'This used __invoke().';
  }
}
