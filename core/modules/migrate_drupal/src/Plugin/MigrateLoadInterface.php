<?php

/**
 * @file
 * Contains Drupal\migrate\Plugin\MigrateLoadInterface
 */

namespace Drupal\migrate_drupal\Plugin;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for migration load plugins.
 *
 * @see \Drupal\migrate_drupal\Plugin\migrate\load\LoadEntity
 *
 * @ingroup migration
 */
interface MigrateLoadInterface {

  /**
   * Load an additional migration.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The migration storage.
   * @param string $sub_id
   *   For example, when loading d6_node:article, this will be article.
   * @return \Drupal\migrate\Entity\MigrationInterface
   */
  public function load(EntityStorageInterface $storage, $sub_id);

  /**
   * Load additional migrations.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The migration storage.
   * @param array $sub_ids
   *   For example, when loading d6_node:article, sub_id will be article.
   *   If NULL then load all sub-migrations.
   * @return \Drupal\migrate\Entity\MigrationInterface[]
   */
  public function loadMultiple(EntityStorageInterface $storage, array $sub_ids = NULL);

}
