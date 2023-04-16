<?php

namespace Drupal\Tests\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Component\DependencyInjection\PhpArrayContainer
 * @group DependencyInjection
 */
class PhpArrayContainerTest extends ContainerTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->machineFormat = FALSE;
    $this->containerClass = '\Drupal\Component\DependencyInjection\PhpArrayContainer';
    $this->containerDefinition = $this->getMockContainerDefinition();
    $this->container = new $this->containerClass($this->containerDefinition);
  }

  /**
   * Helper function to return a service definition.
   */
  protected function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
    if ($invalid_behavior !== ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      return sprintf('@?%s', $id);
    }

    return sprintf('@%s', $id);
  }

  /**
   * Helper function to return a service definition.
   */
  protected function getParameterCall($name) {
    return '%' . $name . '%';
  }

  /**
   * Helper function to return a machine-optimized '@notation'.
   */
  protected function getCollection($collection, $resolve = TRUE) {
    return $collection;
  }

}
