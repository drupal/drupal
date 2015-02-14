<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\MigrateStorage.
 */

namespace Drupal\migrate_drupal;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\migrate\MigrationStorage as BaseMigrationStorage;

/**
 * Storage for migration entities.
 */
class MigrationStorage extends BaseMigrationStorage {

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $ids_to_load = array();
    $dynamic_ids = array();
    if (isset($ids)) {
      foreach ($ids as $id) {
        // Evaluate whether or not this migration is dynamic in the form of
        // migration_id:* to load all the additional migrations.
        if (($n = strpos($id, ':')) !== FALSE) {
          $base_id = substr($id, 0, $n);
          $ids_to_load[] = $base_id;
          // Get the ids of the additional migrations.
          $sub_id = substr($id, $n + 1);
          if ($sub_id == '*') {
            // If the id of the additional migration is '*', get all of them.
            $dynamic_ids[$base_id] = NULL;
          }
          elseif (!isset($dynamic_ids[$base_id]) || is_array($dynamic_ids[$base_id])) {
            $dynamic_ids[$base_id][] = $sub_id;
          }
        }
        else {
          $ids_to_load[] = $id;
        }
      }
      $ids = array_flip($ids);
    }
    else {
      $ids_to_load = NULL;
    }

    /** @var \Drupal\migrate_drupal\Entity\MigrationInterface[] $entities */
    $entities = parent::loadMultiple($ids_to_load);
    if (!isset($ids)) {
      // Changing the array being foreach()'d is not a good idea.
      $return = array();
      foreach ($entities as $entity_id => $entity) {
        if ($plugin = $entity->getLoadPlugin()) {
          $new_entities = $plugin->loadMultiple($this);
          $this->postLoad($new_entities);
          $this->getDynamicIds($dynamic_ids, $new_entities);
          $return += $new_entities;
        }
        else {
          $return[$entity_id] = $entity;
        }
      }
      $entities = $return;
    }
    else {
      foreach ($dynamic_ids as $base_id => $sub_ids) {
        $entity = $entities[$base_id];
        if ($plugin = $entity->getLoadPlugin()) {
          unset($entities[$base_id]);
          $new_entities = $plugin->loadMultiple($this, $sub_ids);
          $this->postLoad($new_entities);
          if (!isset($sub_ids)) {
            unset($dynamic_ids[$base_id]);
            $this->getDynamicIds($dynamic_ids, $new_entities);
          }
          $entities += $new_entities;
        }
      }
    }

    // Build an array of dependencies and set the order of the migrations.
    return $this->buildDependencyMigration($entities, $dynamic_ids);
  }

  /**
   * Extract the dynamic id mapping from entities loaded by plugin.
   *
   * @param array $dynamic_ids
   *   Get the dynamic migration ids.
   * @param array $entities
   *   An array of entities.
   */
  protected function getDynamicIds(array &$dynamic_ids, array $entities) {
    foreach (array_keys($entities) as $new_id) {
      list($base_id, $sub_id) = explode(':', $new_id, 2);
      $dynamic_ids[$base_id][] = $sub_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if (strpos($entity->id(), ':') !== FALSE) {
      throw new EntityStorageException(String::format("Dynamic migration %id can't be saved", array('$%id' => $entity->id())));
    }
    return parent::save($entity);
  }

}
