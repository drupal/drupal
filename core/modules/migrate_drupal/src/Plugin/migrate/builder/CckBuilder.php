<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\builder\CckBuilder.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\builder;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
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
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache = [];

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

  /**
   * Gets a cckfield plugin instance.
   *
   * @param string $field_type
   *   The field type (plugin ID).
   * @param \Drupal\migrate\Entity\MigrationInterface|NULL $migration
   *   The migration, if any.
   *
   * @return \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface
   *   The cckfield plugin instance.
   */
  protected function getCckPlugin($field_type, MigrationInterface $migration = NULL) {
    if (empty($this->cckPluginCache[$field_type])) {
      $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, [], $migration);
    }
    return $this->cckPluginCache[$field_type];
  }

}
