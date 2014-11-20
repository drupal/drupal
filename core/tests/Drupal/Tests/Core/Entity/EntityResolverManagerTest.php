<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityResolverManagerTest.
 */

namespace Drupal\Tests\Core\Entity {

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityResolverManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityResolverManager
 * @group Entity
 */
class EntityResolverManagerTest extends UnitTestCase {

  /**
   * The tested entity resolver manager.
   *
   * @var \Drupal\Core\Entity\EntityResolverManager
   */
  protected $entityResolverManager;

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $classResolver;

  /**
   * The mocked dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct()
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $this->classResolver = $this->getClassResolverStub();

    $this->entityResolverManager = new EntityResolverManager($this->entityManager, $this->classResolver);
  }

  /**
   * Tests setRouteOptions() with no parameter.
   *
   * We don't have any entity type involved, so we don't need any upcasting.
   *
   * @covers ::setRouteOptions
   * @covers ::getControllerClass
   *
   * @dataProvider providerTestSetRouteOptionsWithStandardRoute
   */
  public function testSetRouteOptionsWithStandardRoute($controller) {
    $route = new Route('/example', array(
      '_controller' => $controller,
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $this->assertEmpty($route->getOption('parameters'));
  }

  /**
   * Data provider for testSetRouteOptionsWithStandardRoute.
   */
  public function providerTestSetRouteOptionsWithStandardRoute() {
    return array(
      array('Drupal\Tests\Core\Entity\BasicControllerClass::exampleControllerMethod'),
      array('test_function_controller'),
    );
  }

  /**
   * Tests setRouteOptions() with a controller with a non entity argument.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   *
   * @dataProvider providerTestSetRouteOptionsWithStandardRouteWithArgument
   */
  public function testSetRouteOptionsWithStandardRouteWithArgument($controller) {
    $route = new Route('/example/{argument}', array(
      '_controller' => $controller,
      'argument' => 'test',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $this->assertEmpty($route->getOption('parameters'));
  }

  /**
   * Data provider for testSetRouteOptionsWithStandardRouteWithArgument.
   */
  public function providerTestSetRouteOptionsWithStandardRouteWithArgument() {
    return array(
      array('Drupal\Tests\Core\Entity\BasicControllerClass::exampleControllerMethodWithArgument'),
      array('test_function_controller_with_argument'),
    );
  }

  /**
   * Tests setRouteOptions() with a _content default.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   *
   * @dataProvider providerTestSetRouteOptionsWithContentController
   */
  public function testSetRouteOptionsWithContentController($controller) {
    $route = new Route('/example/{argument}', array(
      '_controller' => $controller,
      'argument' => 'test',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $this->assertEmpty($route->getOption('parameters'));
  }

  /**
   * Data provider for testSetRouteOptionsWithContentController.
   */
  public function providerTestSetRouteOptionsWithContentController() {
    return array(
      array('Drupal\Tests\Core\Entity\BasicControllerClass::exampleControllerMethodWithArgument'),
      array('test_function_controller_with_argument'),
    );
  }

  /**
   * Tests setRouteOptions() with an entity type parameter.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   *
   * @dataProvider providerTestSetRouteOptionsWithEntityTypeNoUpcasting
   */
  public function testSetRouteOptionsWithEntityTypeNoUpcasting($controller) {
    $this->setupEntityTypes();

    $route = new Route('/example/{entity_test}', array(
      '_controller' => $controller,
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $this->assertEmpty($route->getOption('parameters'));
  }

  /**
   * Data provider for testSetRouteOptionsWithEntityTypeNoUpcasting.
   */
  public function providerTestSetRouteOptionsWithEntityTypeNoUpcasting() {
    return array(
      array('Drupal\Tests\Core\Entity\BasicControllerClass::exampleControllerWithEntityNoUpcasting'),
      array('test_function_controller_no_upcasting'),
    );
  }

  /**
   * Tests setRouteOptions() with an entity type parameter, upcasting.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   *
   * @dataProvider providerTestSetRouteOptionsWithEntityTypeUpcasting
   */
  public function testSetRouteOptionsWithEntityTypeUpcasting($controller) {
    $this->setupEntityTypes();

    $route = new Route('/example/{entity_test}', array(
      '_controller' => $controller,
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertEquals(array('entity_test' => array('type' => 'entity:entity_test')), $parameters);
  }

  /**
   * Data provider for testSetRouteOptionsWithEntityTypeUpcasting.
   */
  public function providerTestSetRouteOptionsWithEntityTypeUpcasting() {
    return array(
      array('Drupal\Tests\Core\Entity\BasicControllerClass::exampleControllerWithEntityUpcasting'),
      array('test_function_controller_entity_upcasting'),
    );
  }

  /**
   * Tests setRouteOptions() with an entity type parameter form.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   */
  public function testSetRouteOptionsWithEntityFormUpcasting() {
    $this->setupEntityTypes();

    $route = new Route('/example/{entity_test}', array(
      '_form' => 'Drupal\Tests\Core\Entity\BasicForm',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertEquals(array('entity_test' => array('type' => 'entity:entity_test')), $parameters);
  }

  /**
   * Tests setRouteOptions() with entity form upcasting, no create method.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   */
  public function testSetRouteOptionsWithEntityUpcastingNoCreate() {
    $this->setupEntityTypes();

    $route = new Route('/example/{entity_test}', array(
      '_form' => 'Drupal\Tests\Core\Entity\BasicFormNoContainerInjectionInterface',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertEquals(array('entity_test' => array('type' => 'entity:entity_test')), $parameters);
  }

  /**
   * Tests setRouteOptions() with an form parameter without interface.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   */
  public function testSetRouteOptionsWithEntityFormNoUpcasting() {
    $this->setupEntityTypes();

    $route = new Route('/example/{entity_test}', array(
      '_form' => 'Drupal\Tests\Core\Entity\BasicFormNoUpcasting',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $this->assertEmpty($route->getOption('parameters'));
  }

  /**
   * Tests setRouteOptions() with an _entity_view route.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   * @covers ::setParametersFromEntityInformation()
   */
  public function testSetRouteOptionsWithEntityViewRouteAndManualParameters() {
    $this->setupEntityTypes();
    $route = new Route('/example/{foo}',
      array(
        '_entity_view' => 'entity_test.view',
      ),
      array(),
      array(
        'parameters' => array(
          'foo' => array(
            'type' => 'entity:entity_test',
          ),
        ),
      )
    );

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertEquals(array('foo' => array('type' => 'entity:entity_test')), $parameters);
  }

  /**
   * Tests setRouteOptions() with an _entity_view route.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   * @covers ::setParametersFromEntityInformation()
   */
  public function testSetRouteOptionsWithEntityViewRoute() {
    $this->setupEntityTypes();
    $route = new Route('/example/{entity_test}', array(
      '_entity_view' => 'entity_test.view',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertEquals(array('entity_test' => array('type' => 'entity:entity_test')), $parameters);
  }

  /**
   * Tests setRouteOptions() with an _entity_list route.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   * @covers ::setParametersFromEntityInformation()
   */
  public function testSetRouteOptionsWithEntityListRoute() {
    $this->setupEntityTypes();
    $route = new Route('/example/{entity_test}', array(
      '_entity_list' => 'entity_test',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertNull($parameters);
  }

  /**
   * Tests setRouteOptions() with an _entity_form route.
   *
   * @covers ::setRouteOptions()
   * @covers ::getControllerClass()
   * @covers ::getEntityTypes()
   * @covers ::setParametersFromReflection()
   * @covers ::setParametersFromEntityInformation()
   */
  public function testSetRouteOptionsWithEntityFormRoute() {
    $this->setupEntityTypes();
    $route = new Route('/example/{entity_test}', array(
      '_entity_form' => 'entity_test.edit',
    ));

    $defaults = $route->getDefaults();
    $this->entityResolverManager->setRouteOptions($route);
    $this->assertEquals($defaults, $route->getDefaults());
    $parameters = $route->getOption('parameters');
    $this->assertEquals(array('entity_test' => array('type' => 'entity:entity_test')), $parameters);
  }

  /**
   * Creates the entity manager mock returning entity type objects.
   */
  protected function setupEntityTypes() {
    $definition = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $definition->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue('Drupal\Tests\Core\Entity\SimpleTestEntity'));
    $this->entityManager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array(
        'entity_test' => $definition,
      )));
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnCallback(function ($entity_type) use ($definition) {
        if ($entity_type == 'entity_test') {
          return $definition;
        }
        else {
          return NULL;
        }
      }));
  }

}

/**
 * A class containing all kind of different controller methods.
 */
class BasicControllerClass {

  public function exampleControllerMethod() {
  }

  public function exampleControllerMethodWithArgument($argument) {
  }

  public function exampleControllerWithEntityNoUpcasting($entity_test) {
  }

  public function exampleControllerWithEntityUpcasting(EntityInterface $entity_test) {
  }

}

/**
 * A concrete entity.
 */
class SimpleTestEntity extends Entity {

}

/**
 * A basic form with a passed entity with an interface.
 */
class BasicForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity_test = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}

/**
 * A basic form with a passed entity without an interface.
 */
class BasicFormNoUpcasting extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_test = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}

class BasicFormNoContainerInjectionInterface implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity_test = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}

}

namespace {

  use Drupal\Core\Entity\EntityInterface;

  function test_function_controller() {
  }

  function test_function_controller_with_argument($argument) {
  }

  function test_function_controller_no_upcasting($entity_test) {
  }

  function test_function_controller_entity_upcasting(EntityInterface $entity_test) {
  }
}
