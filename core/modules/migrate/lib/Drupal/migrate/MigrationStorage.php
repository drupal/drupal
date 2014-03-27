<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateStorage.
 */

namespace Drupal\migrate;

use Drupal\Component\Graph\Graph;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage controller for migration entities.
 */
class MigrationStorage extends ConfigEntityStorage implements MigrateBuildDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function buildDependencyMigration(array $migrations, array $dynamic_ids) {
    // Migration dependencies defined in the migration storage can be
    // soft dependencies: if a soft dependency does not run, the current
    // migration is still OK to go. This is indicated by adding ": false"
    // (without quotes) after the name of the dependency. Hard dependencies
    // (default) are called requirements. Both hard and soft dependencies (if
    // run at all) must run before the current one.
    $dependency_graph = array();
    $requirement_graph = array();
    $different = FALSE;
    foreach ($migrations as $migration) {
      /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
      $id = $migration->id();
      $requirements[$id] = array();
      $dependency_graph[$id]['edges'] = array();
      if (isset($migration->migration_dependencies) && is_array($migration->migration_dependencies)) {
        foreach ($migration->migration_dependencies as $dependency) {
          if (is_string($dependency) && !isset($dynamic_ids[$dependency])) {
            $this->addDependency($requirement_graph, $id, $dependency, $dynamic_ids);
          }
          if (is_array($dependency)) {
            list($dependency_string, $required) = each($dependency);
            $dependency = $dependency_string;
            if ($required) {
              $this->addDependency($requirement_graph, $id, $dependency, $dynamic_ids);
            }
            else {
              $different = TRUE;
            }
          }
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
