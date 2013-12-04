<?php

/**
 * @file
 * Contains \Drupal\config_translation\Plugin\Derivative\ConfigTranslationLocalTasks.
 */

namespace Drupal\config_translation\Plugin\Derivative;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Menu\LocalTaskDerivativeBase;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for config translation.
 */
class ConfigTranslationLocalTasks extends LocalTaskDerivativeBase implements ContainerDerivativeInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a new ConfigTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct($base_plugin_id, ConfigMapperManagerInterface $mapper_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.config_translation.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $plugin_id => $mapper) {
      /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
      $route_name = $mapper->getOverviewRouteName();
      $this->derivatives[$route_name] = $base_plugin_definition;
      $this->derivatives[$route_name]['config_translation_plugin_id'] = $plugin_id;
      $this->derivatives[$route_name]['class'] = '\Drupal\config_translation\Plugin\Menu\LocalTask\ConfigTranslationLocalTask';
      $this->derivatives[$route_name]['route_name'] = $route_name;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  /**
   * Alters the local tasks to find the proper tab_root_id for each task.
   */
  public function alterLocalTasks(array &$local_tasks) {
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $mapper) {
      /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
      $route_name = $mapper->getOverviewRouteName();
      $translation_tab = $this->basePluginId . ':' . $route_name;
      $tab_root_id = $this->getPluginIdFromRoute($mapper->getBaseRouteName(), $local_tasks);
      if (!empty($tab_root_id)) {
        $local_tasks[$translation_tab]['tab_root_id'] = $tab_root_id;
      }
      else {
        unset($local_tasks[$translation_tab]);
      }
    }
  }


}
