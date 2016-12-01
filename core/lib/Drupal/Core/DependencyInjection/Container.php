<?php

namespace Drupal\Core\DependencyInjection;

use Drupal\Component\DependencyInjection\Container as DrupalContainer;

/**
 * Extends the Drupal container to set the service ID on the created object.
 */
class Container extends DrupalContainer {

  /**
   * {@inheritdoc}
   */
  public function set($id, $service) {
    parent::set($id, $service);

    // Ensure that the _serviceId property is set on synthetic services as well.
    if (isset($this->services[$id]) && is_object($this->services[$id]) && !isset($this->services[$id]->_serviceId)) {
      $this->services[$id]->_serviceId = $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    assert(FALSE, 'The container was serialized.');
    return array_keys(get_object_vars($this));
  }

}
