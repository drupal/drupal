<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins supporting metadata inspection and translation.
 *
 * @ingroup plugin_api
 */
abstract class PluginBase extends ComponentPluginBase {

  use AutowiredInstanceTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;
  use MessengerTrait;

  /**
   * Instantiates a new instance of the implementing class using autowiring.
   *
   * @see \Drupal\Core\Plugin\ContainerFactoryPluginInterface
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return static::createInstanceAutowired($container, $configuration, $plugin_id, $plugin_definition);
  }

}
