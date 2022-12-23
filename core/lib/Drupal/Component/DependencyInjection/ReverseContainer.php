<?php

namespace Drupal\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;

/**
 * Retrieves service IDs from the container for public services.
 *
 * Heavily inspired by \Symfony\Component\DependencyInjection\ReverseContainer.
 */
final class ReverseContainer {

  /**
   * The service container.
   *
   * @var \Drupal\Component\DependencyInjection\Container|\Symfony\Component\DependencyInjection\Container
   */
  private $serviceContainer;

  /**
   * A closure on the container that can search for services.
   *
   * @var \Closure
   */
  private $getServiceId;

  /**
   * A static map of services to a hash.
   *
   * @var array
   */
  private static $recordedServices = [];

  /**
   * Constructs a ReverseContainer object.
   *
   * @param \Drupal\Component\DependencyInjection\Container|\Symfony\Component\DependencyInjection\Container $serviceContainer
   *   The service container.
   */
  public function __construct($serviceContainer) {
    if (!($serviceContainer instanceof Container || $serviceContainer instanceof SymfonyContainer)) {
      throw new \InvalidArgumentException('The container must be an instance of \Drupal\Component\DependencyInjection\Container or \Symfony\Component\DependencyInjection\Container');
    }
    $this->serviceContainer = $serviceContainer;
    $this->getServiceId = \Closure::bind(function ($service): ?string {
      return array_search($service, $this->services, TRUE) ?: NULL;
    }, $serviceContainer, $serviceContainer);
  }

  /**
   * Returns the ID of the passed object when it exists as a service.
   *
   * To be reversible, services need to be public.
   *
   * @param object $service
   *   The service to find the ID for.
   */
  public function getId($service): ?string {
    if ($this->serviceContainer === $service || $service instanceof SymfonyContainerInterface) {
      return 'service_container';
    }

    $hash = $this->generateServiceIdHash($service);
    $id = self::$recordedServices[$hash] ?? ($this->getServiceId)($service);

    if ($id !== NULL && $this->serviceContainer->has($id)) {
      self::$recordedServices[$hash] = $id;
      return $id;
    }

    return NULL;
  }

  /**
   * Records a map of the container's services.
   *
   * This method is used so that stale services can be serialized after a
   * container has been re-initialized.
   */
  public function recordContainer(): void {
    $service_recorder = \Closure::bind(function () : array {
      return array_filter($this->services, 'is_object');
    }, $this->serviceContainer, $this->serviceContainer);
    self::$recordedServices = array_merge(self::$recordedServices, array_flip(array_map([$this, 'generateServiceIdHash'], $service_recorder())));
  }

  /**
   * Generates an identifier for a service based on the object class and hash.
   *
   * @param object $object
   *   The object to generate an identifier for.
   *
   * @return string
   *   The object's class and hash concatenated together.
   */
  private function generateServiceIdHash(object $object): string {
    // Include class name as an additional namespace for the hash since
    // spl_object_hash's return can be recycled. This still is not a 100%
    // guarantee to be unique but makes collisions incredibly difficult and even
    // then the interface would be preserved.
    // @see https://php.net/spl_object_hash#refsect1-function.spl-object-hash-notes
    return get_class($object) . spl_object_hash($object);
  }

}
