<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\ContainerFactoryPluginBase.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base plugin that can pull its dependencies from the container.
 */
abstract class ContainerFactoryPluginBase extends PluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
