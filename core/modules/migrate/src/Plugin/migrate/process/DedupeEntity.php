<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures value is not duplicated against an entity field.
 *
 * If the 'migrated' configuration value is true, an entity will only be
 * considered a duplicate if it was migrated by the current migration.
 *
 * @link https://www.drupal.org/node/2135325 Online handbook documentation for dedupe_entity process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "dedupe_entity"
 * )
 */
class DedupeEntity extends DedupeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $entityQueryFactory;

  /**
   * The current migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, QueryFactory $entity_query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->entityQueryFactory = $entity_query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function exists($value) {
    // Plugins are cached so for every run we need a new query object.
    $query = $this
      ->entityQueryFactory
      ->get($this->configuration['entity_type'], 'AND')
      ->condition($this->configuration['field'], $value);
    if (!empty($this->configuration['migrated'])) {
      // Check if each entity is in the ID map.
      $idMap = $this->migration->getIdMap();
      foreach ($query->execute() as $id) {
        $dest_id_values[$this->configuration['field']] = $id;
        if ($idMap->lookupSourceID($dest_id_values)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    else {
      // Just check if any such entity exists.
      return $query->count()->execute();
    }
  }

}
