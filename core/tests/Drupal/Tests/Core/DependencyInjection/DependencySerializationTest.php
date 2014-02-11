<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\DependencySerializationTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the dependency serialization base class.
 *
 * @see \Drupal\Core\DependencyInjection\DependencySerialization
 */
class DependencySerializationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Service dependency serialization',
      'description' => 'Tests the dependency serialization base class.',
      'group' => 'System'
    );
  }

  /**
   * Tests serialization and unserialization.
   */
  public function testSerialization() {
    // Create a pseudo service and dependency injected object.
    $service = new \stdClass();
    $service->_serviceId = 'test_service';
    $container = new Container();
    $container->set('test_service', $service);
    $container->set('service_container', $container);
    \Drupal::setContainer($container);

    $dependencySerialization = new TestClass($service);
    $dependencySerialization->setContainer($container);

    $string = serialize($dependencySerialization);
    $object = unserialize($string);

    // The original object got _serviceIds added so removing it to check
    // equality.
    unset($dependencySerialization->_serviceIds);

    // Ensure dependency injected object remains the same after serialization.
    $this->assertEquals($dependencySerialization, $object);

    // Ensure that _serviceIds does not exist on the object anymore.
    $this->assertFalse(isset($object->_serviceIds));

    // Ensure that both the service and the variable are in the unserialized
    // object.
    $this->assertSame($service, $object->service);
    $this->assertSame($container, $object->container);
  }

}

/**
 * Defines a test class which has a single service as dependency.
 */
class TestClass extends DependencySerialization implements ContainerAwareInterface {

  /**
   * A test service.
   *
   * @var \stdClass
   */
  public $service;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public $container;

  /**
   * {@inheritdoc}
   *
   * Make the property accessible for the test.
   */
  public $_serviceIds;

  /**
   * Constructs a new TestClass object.
   *
   * @param \stdClass $service
   *   A test service.
   */
  public function __construct(\stdClass $service) {
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }
}
