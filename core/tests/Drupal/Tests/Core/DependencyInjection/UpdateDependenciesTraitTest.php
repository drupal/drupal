<?php

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\UpdateDependenciesTrait;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\UpdateDependenciesTrait
 * @group DependencyInjection
 */
class UpdateDependenciesTraitTest extends UnitTestCase {

  /**
   * @covers ::updateDependencies
   */
  public function testUpdateDependencies() {
    $service = new \StdClass();
    $service->_serviceId = 'test_service';

    $container = new ContainerBuilder();
    $container->set('test_service', $service);

    $trait_test = new UpdateDependenciesTraitTestClass();
    $trait_test->dependency = clone $service;
    $trait_test->container = $container;

    $object = new \StdClass();
    $trait_test->object = $object;

    // Ensure that the service and container are updated but other objects are
    // untouched.
    $trait_test->updateDependenciesPublic($container);

    $this->assertSame($service, $trait_test->dependency);
    $this->assertSame($container, $trait_test->container);
    $this->assertSame($object, $trait_test->object);
  }

}

class UpdateDependenciesTraitTestClass {

  use UpdateDependenciesTrait;

  /**
   * Updates dependencies.
   *
   * A wrapper around the protected UpdateDependenciesTrait::updateDependencies
   * method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function updateDependenciesPublic(ContainerInterface $container) {
    $this->updateDependencies($container);
  }
}
