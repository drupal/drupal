<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Container as SymfonyContainer;

/**
 * Extends the symfony container to set the service ID on the created object.
 */
class Container extends SymfonyContainer {

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE) {
    $service = parent::get($id, $invalidBehavior);
    // Some services are called but do not exist, so the parent returns nothing.
    if (is_object($service)) {
      $service->_serviceId = $id;
    }

    return $service;
  }

}
