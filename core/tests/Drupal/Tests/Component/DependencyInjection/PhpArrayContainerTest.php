<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\DependencyInjection;

use Drupal\Component\DependencyInjection\PhpArrayContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests Drupal\Component\DependencyInjection\PhpArrayContainer.
 */
#[CoversClass(PhpArrayContainer::class)]
#[Group('DependencyInjection')]
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
  protected function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): string {
    if ($invalid_behavior !== ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      return sprintf('@?%s', $id);
    }

    return sprintf('@%s', $id);
  }

  /**
   * Helper function to return a service definition.
   */
  protected function getParameterCall($name): string {
    return '%' . $name . '%';
  }

  /**
   * Helper function to return a machine-optimized '@notation'.
   */
  protected function getCollection($collection, $resolve = TRUE) {
    return $collection;
  }

}
