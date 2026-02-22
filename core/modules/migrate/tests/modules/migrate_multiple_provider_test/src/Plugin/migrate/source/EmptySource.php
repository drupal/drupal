<?php

declare(strict_types=1);

namespace Drupal\migrate_multiple_provider_test\Plugin\migrate\source;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\EmptySource as BaseEmptySource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source for testing multiple providers.
 *
 * @MigrateSource(
 *   id = "test_empty",
 *   source_module = "system",
 * )
 */
class EmptySource extends BaseEmptySource implements ContainerFactoryPluginInterface, DependentPluginInterface {

  use DependencyTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    // The empty source plugin supports the entity_type constant.
    if (isset($this->configuration['constants']['entity_type'])) {
      $this->addDependency('module', $this->entityTypeManager->getDefinition($this->configuration['constants']['entity_type'])->getProvider());
    }
    return $this->dependencies;
  }

}
