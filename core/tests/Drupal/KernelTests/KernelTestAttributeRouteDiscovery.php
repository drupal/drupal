<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\Routing\AttributeRouteDiscovery;

/**
 * Discovers routes in a kernel test class using Symfony's Route attribute.
 *
 * @internal
 */
class KernelTestAttributeRouteDiscovery extends AttributeRouteDiscovery {

  public function __construct(protected readonly string $kernelTestClass) {}

  /**
   * {@inheritdoc}
   */
  protected function collectRoutes(): iterable {
    $reflectionClass = $this->getReflectionClass($this->kernelTestClass);
    if ($reflectionClass !== NULL) {
      yield $this->createControllerRouteCollection($reflectionClass);
    }
  }

}
