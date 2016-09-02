<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures value is not duplicated against an entity field.
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, QueryFactory $entity_query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    return $this
      ->entityQueryFactory
      ->get($this->configuration['entity_type'], 'AND')
      ->condition($this->configuration['field'], $value)
      ->count()
      ->execute();
  }

}
