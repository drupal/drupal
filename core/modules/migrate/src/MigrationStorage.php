<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrationStorage.
 */

namespace Drupal\migrate;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage for migration entities.
 */
class MigrationStorage extends ConfigEntityStorage implements MigrateBuildDependencyInterface {

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $queryFactory;

  /**
   * Constructs a MigrationStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\Query\QueryFactoryInterface $query_factory
   *   The entity query factory service.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, QueryFactoryInterface $query_factory) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.query.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    /** @var \Drupal\migrate\Entity\MigrationInterface[] $migrations */
    $migrations = parent::loadMultiple($ids);

    foreach ($migrations as $migration) {
      $migration->set('migration_dependencies', $this->expandDependencies($migration->getMigrationDependencies()));
    }
    return $migrations;
  }

  /**
   * Expands template dependencies.
   *
   * Migration dependencies which match the template_id:* pattern are a signal
   * that the migration depends on every variant of template_id. This method
   * queries for those variant IDs and splices them into the list of
   * dependencies.
   *
   * @param array $dependencies
   *   The original migration dependencies (with template IDs), organized by
   *   group (required, optional, etc.)
   *
   * @return array
   *   The expanded list of dependencies, organized by group.
   */
  protected function expandDependencies(array $dependencies) {
    $expanded_dependencies = [];

    foreach (array_keys($dependencies) as $group) {
      $expanded_dependencies[$group] = [];

      foreach ($dependencies[$group] as $dependency_id) {
        if (substr($dependency_id, -2) == ':*') {
          $template_id = substr($dependency_id, 0, -2);
          $variants = $this->queryFactory->get($this->entityType, 'OR')
            ->condition('id', $template_id)
            ->condition('template', $template_id)
            ->execute();
          $expanded_dependencies[$group] = array_merge($expanded_dependencies[$group], $variants);
        }
        else {
          $expanded_dependencies[$group][] = $dependency_id;
        }
      }
    }

    return $expanded_dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function buildDependencyMigration(array $migrations, array $dynamic_ids) {
    // Migration dependencies defined in the migration storage can be
    // optional or required. If an optional dependency does not run, the current
    // migration is still OK to go. Both optional and required dependencies
    // (if run at all) must run before the current migration.
    $dependency_graph = array();
    $requirement_graph = array();
    $different = FALSE;
    foreach ($migrations as $migration) {
      /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
      $id = $migration->id();
      $requirements[$id] = array();
      $dependency_graph[$id]['edges'] = array();
      $migration_dependencies = $migration->getMigrationDependencies();

      if (isset($migration_dependencies['required'])) {
        foreach ($migration_dependencies['required'] as $dependency) {
          if (!isset($dynamic_ids[$dependency])) {
            $this->addDependency($requirement_graph, $id, $dependency, $dynamic_ids);
          }
          $this->addDependency($dependency_graph, $id, $dependency, $dynamic_ids);
        }
      }
      if (isset($migration_dependencies['optional'])) {
        foreach ($migration_dependencies['optional'] as $dependency) {
          $different = TRUE;
          $this->addDependency($dependency_graph, $id, $dependency, $dynamic_ids);
        }
      }
    }
    $graph_object = new Graph($dependency_graph);
    $dependency_graph = $graph_object->searchAndSort();
    if ($different) {
      $graph_object = new Graph($requirement_graph);
      $requirement_graph = $graph_object->searchAndSort();
    }
    else {
      $requirement_graph = $dependency_graph;
    }
    $weights = array();
    foreach ($migrations as $migration_id => $migration) {
      // Populate a weights array to use with array_multisort later.
      $weights[] = $dependency_graph[$migration_id]['weight'];
      if (!empty($requirement_graph[$migration_id]['paths'])) {
        $migration->set('requirements', $requirement_graph[$migration_id]['paths']);
      }
    }
    array_multisort($weights, SORT_DESC, SORT_NUMERIC, $migrations);

    return $migrations;
  }

  /**
   * Add one or more dependencies to a graph.
   *
   * @param array $graph
   *   The graph so far.
   * @param int $id
   *   The migration id.
   * @param string $dependency
   *   The dependency string.
   * @param array $dynamic_ids
   *   The dynamic id mapping.
   */
  protected function addDependency(array &$graph, $id, $dependency, $dynamic_ids) {
    $dependencies = isset($dynamic_ids[$dependency]) ? $dynamic_ids[$dependency] : array($dependency);
    if (!isset($graph[$id]['edges'])) {
      $graph[$id]['edges'] = array();
    }
    $graph[$id]['edges'] += array_combine($dependencies, $dependencies);
  }

}
