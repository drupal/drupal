<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\DependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\EmptySource as BaseEmptySource;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Source returning an empty row with Drupal specific config dependencies.
 *
 * @MigrateSource(
 *   id = "md_empty",
 *   source_module = "system",
 * )
 */
class EmptySource extends BaseEmptySource implements ContainerFactoryPluginInterface, DependentPluginInterface {

  use DependencyTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityManager = $entity_manager;
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
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // The empty source plugin supports the entity_type constant.
    if (isset($this->configuration['constants']['entity_type'])) {
      $this->addDependency('module', $this->entityManager->getDefinition($this->configuration['constants']['entity_type'])->getProvider());
    }
    return $this->dependencies;
  }

}
