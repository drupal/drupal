<?php

namespace Drupal\config_translation\Plugin\Derivative;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for config translation.
 */
class ConfigTranslationLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a new ConfigTranslationLocalTasks.
   *
   * @param string $basePluginId
   *   The base plugin ID.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapperManager
   *   The mapper plugin discovery service.
   */
  public function __construct(
    protected $basePluginId,
    protected ConfigMapperManagerInterface $mapperManager,
  ) {
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
  public function getDerivativeDefinitions($base_plugin_definition) {
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $plugin_id => $mapper) {
      /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
      $route_name = $mapper->getOverviewRouteName();
      $base_route = $mapper->getBaseRouteName();
      if (!empty($base_route)) {
        $this->derivatives[$route_name] = $base_plugin_definition;
        $this->derivatives[$route_name]['config_translation_plugin_id'] = $plugin_id;
        $this->derivatives[$route_name]['class'] = '\Drupal\config_translation\Plugin\Menu\LocalTask\ConfigTranslationLocalTask';
        $this->derivatives[$route_name]['route_name'] = $route_name;
        $this->derivatives[$route_name]['base_route'] = $base_route;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
