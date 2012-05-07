<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;

/**
 * Drupal's dependency injection container.
 */
class ContainerBuilder extends BaseContainerBuilder {

  /**
   * Registers the base Drupal services for the dependency injection container.
   */
  public function __construct() {
    parent::__construct();

    // An interface language always needs to be available for t() and other
    // functions. This default is overridden by drupal_language_initialize()
    // during language negotiation.
    $this->register(LANGUAGE_TYPE_INTERFACE, 'Drupal\\Core\\Language\\Language');
  }
}
