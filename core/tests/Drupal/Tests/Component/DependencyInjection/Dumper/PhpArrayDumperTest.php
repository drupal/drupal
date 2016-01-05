<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\DependencyInjection\Dumper\PhpArrayDumperTest.
 */

namespace Drupal\Tests\Component\DependencyInjection\Dumper;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper
 * @group DependencyInjection
 */
class PhpArrayDumperTest extends OptimizedPhpArrayDumperTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->machineFormat = FALSE;
    $this->dumperClass = '\Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function serializeDefinition(array $service_definition) {
    return $service_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
    if ($invalid_behavior !== ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      return sprintf('@?%s', $id);
    }

    return sprintf('@%s', $id);
  }

  /**
   * {@inheritdoc}
   */
  protected function getParameterCall($name) {
    return '%' . $name . '%';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollection($collection, $resolve = TRUE) {
    return $collection;
  }

}
