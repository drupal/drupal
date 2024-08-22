<?php

declare(strict_types=1);

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
  protected function setUp(): void {
    $this->machineFormat = FALSE;
    $this->dumperClass = '\Drupal\Component\DependencyInjection\Dumper\PhpArrayDumper';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected static function serializeDefinition(array $service_definition) {
    return $service_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): string {
    if ($invalid_behavior !== ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      return sprintf('@?%s', $id);
    }

    return sprintf('@%s', $id);
  }

  /**
   * {@inheritdoc}
   */
  protected static function getParameterCall($name): string {
    return '%' . $name . '%';
  }

  /**
   * {@inheritdoc}
   */
  protected static function getCollection($collection, $resolve = TRUE) {
    return $collection;
  }

}
