<?php

namespace Drupal\block\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateProcessPlugin(
 *   id = "block_region"
 * )
 */
class BlockRegion extends StaticMap implements ContainerFactoryPluginInterface {

  /**
   * List of regions, keyed by theme.
   *
   * @var array[]
   */
  protected $regions;

  /**
   * Constructs a BlockRegion plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param array $regions
   *   Array of region maps, keyed by theme.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $regions) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->regions = $regions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $regions = array();
    foreach ($container->get('theme_handler')->listInfo() as $key => $theme) {
      $regions[$key] = $theme->info['regions'];
    }
    return new static($configuration, $plugin_id, $plugin_definition, $regions);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Set the destination region, based on the source region and theme as well
    // as the current destination default theme.
    list($source_theme, $destination_theme, $region) = $value;

    // Theme is the same on both source and destination, so ensure that the
    // region exists in the destination theme.
    if (strtolower($source_theme) == strtolower($destination_theme)) {
      if (isset($this->regions[$destination_theme][$region])) {
        return $region;
      }
    }

    // Fall back to static mapping.
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
