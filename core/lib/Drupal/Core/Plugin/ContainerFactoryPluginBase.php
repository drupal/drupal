<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContainerFactoryPluginBase.
 */
namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base plugin that can pull it's dependencies from the container.
 */
class ContainerFactoryPluginBase extends PluginBase {

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
