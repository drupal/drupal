<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\DependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\EmptySource as BaseEmptySource;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Source returning an empty row with Drupal specific config dependencies.
 *
 * For more information and available configuration keys, refer to the parent
 * classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\EmptySource
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "md_empty",
 *   source_module = "system",
 * )
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533564
 */
class EmptySource extends BaseEmptySource implements ContainerFactoryPluginInterface, DependentPluginInterface {

  use DependencyTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager) {
    @trigger_error('Migrate source plugin "md_empty" used in migration "' . $migration->id() . '" is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533564', E_USER_DEPRECATED);
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
  public function calculateDependencies() {
    // The empty source plugin supports the entity_type constant.
    if (isset($this->configuration['constants']['entity_type'])) {
      $this->addDependency('module', $this->entityTypeManager->getDefinition($this->configuration['constants']['entity_type'])->getProvider());
    }
    return $this->dependencies;
  }

}
