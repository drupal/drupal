<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\DependencySerializationTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\DependencySerializationTrait
 * @group DependencyInjection
 */
class DependencySerializationTest extends UnitTestCase {

  /**
   * @covers ::__sleep
   * @covers ::__wakeup
   */
  public function testSerialization() {
    // Create a pseudo service and dependency injected object.
    $service = new \stdClass();
    $service->_serviceId = 'test_service';
    $container = new Container();
    $container->set('test_service', $service);
    $container->set('service_container', $container);
    \Drupal::setContainer($container);

    $dependencySerialization = new DependencySerializationTestDummy($service);
    $dependencySerialization->setContainer($container);

    $string = serialize($dependencySerialization);
    /** @var \Drupal\Tests\Core\DependencyInjection\DependencySerializationTestDummy $dependencySerialization */
    $dependencySerialization = unserialize($string);

    $this->assertSame($service, $dependencySerialization->service);
    $this->assertSame($container, $dependencySerialization->container);
    $this->assertEmpty($dependencySerialization->getServiceIds());
  }

  /**
   * @covers ::__sleep
   * @covers ::__wakeup
   */
  public function testSerializationWithMissingService() {
    // Create a pseudo service and dependency injected object.
    $service = new \stdClass();
    $service->_serviceId = 'test_service_not_existing';
    $container = new Container();
    $container->set('test_service', $service);
    $container->set('service_container', $container);
    \Drupal::setContainer($container);

    $dependencySerialization = new DependencySerializationTestDummy($service);
    $dependencySerialization->setContainer($container);

    $string = serialize($dependencySerialization);
    /** @var \Drupal\Tests\Core\DependencyInjection\DependencySerializationTestDummy $dependencySerialization */
    $dependencySerialization = unserialize($string);

    $this->assertSame($container, $dependencySerialization->container);
  }

}

/**
 * Defines a test class which has a single service as dependency.
 */
class DependencySerializationTestDummy implements ContainerAwareInterface {

  use DependencySerializationTrait;

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
   * Constructs a new TestClass object.
   *
   * @param object $service
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

  /**
   * Gets the stored service IDs.
   */
  public function getServiceIds() {
    return $this->_serviceIds;
  }

}
