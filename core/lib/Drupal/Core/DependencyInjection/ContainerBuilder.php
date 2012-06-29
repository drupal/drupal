<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;


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

    // Register the default language content.
    $this->register(LANGUAGE_TYPE_CONTENT, 'Drupal\\Core\\Language\\Language');

    // Register configuration storage dispatcher.
    $this->setParameter('config.storage.info', array(
      'Drupal\Core\Config\DatabaseStorage' => array(
        'connection' => 'default',
        'target' => 'default',
        'read' => TRUE,
        'write' => TRUE,
      ),
      'Drupal\Core\Config\FileStorage' => array(
        'directory' => config_get_config_directory(),
        'read' => TRUE,
        'write' => FALSE,
      ),
    ));
    $this->register('config.storage.dispatcher', 'Drupal\Core\Config\StorageDispatcher')
      ->addArgument('%config.storage.info%');

    // Register configuration object factory.
    $this->register('config.factory', 'Drupal\Core\Config\ConfigFactory')
      ->addArgument(new Reference('config.storage.dispatcher'));
  }
}
