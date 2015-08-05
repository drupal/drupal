<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\builder\d6\CckBuilder.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\builder\d6;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\builder\BuilderBase;
use Drupal\migrate\Plugin\MigratePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for builders which leverage cckfield plugins.
 */
abstract class CckBuilder extends BuilderBase implements ContainerFactoryPluginInterface {

  /**
   * The cckfield plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $cckPluginManager;

  /**
   * Constructs a CckBuilder.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $cck_manager
   *   The cckfield plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigratePluginManager $cck_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cckPluginManager = $cck_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.cckfield')
    );
  }

}
