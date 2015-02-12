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
  public function set($id, $service, $scope = SymfonyContainer::SCOPE_CONTAINER) {
     parent::set($id, $service, $scope);

    // Ensure that the _serviceId property is set on synthetic services as well.
    if (isset($this->services[$id]) && is_object($this->services[$id]) && !isset($this->services[$id]->_serviceId)) {
      $this->services[$id]->_serviceId = $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    trigger_error('The container was serialized.', E_USER_ERROR);
    return array_keys(get_object_vars($this));
  }

}
