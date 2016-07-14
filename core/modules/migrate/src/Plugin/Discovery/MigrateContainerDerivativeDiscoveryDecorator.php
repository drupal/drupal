<?php

namespace Drupal\migrate\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;

/**
 * Overrides ContainerDerivativeDiscoveryDecorator to pre-check providers.
 *
 * @ingroup migration
 */
class MigrateContainerDerivativeDiscoveryDecorator extends ContainerDerivativeDiscoveryDecorator {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new MigrateContainerDerivativeDiscoveryDecorator.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The parent object that is being decorated.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(DiscoveryInterface $decorated, ModuleHandlerInterface $module_handler) {
    parent::__construct($decorated);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeriver($base_plugin_id, $base_definition) {
    // Short-circuit attempts to instantiate derivers if the base provider is
    // not available.
    if (isset($base_definition['provider']) && !in_array($base_definition['provider'], ['core', 'component']) && !$this->moduleHandler->moduleExists($base_definition['provider'])) {
      return NULL;
    }
    else {
      return parent::getDeriver($base_plugin_id, $base_definition);
    }
  }

}
