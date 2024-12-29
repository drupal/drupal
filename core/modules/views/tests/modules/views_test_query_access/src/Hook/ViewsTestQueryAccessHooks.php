<?php

declare(strict_types=1);

namespace Drupal\views_test_query_access\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_query_access.
 */
class ViewsTestQueryAccessHooks {

  /**
   * Implements hook_query_TAG_alter() for the 'media_access' query tag.
   */
  #[Hook('query_media_access_alter')]
  public function queryMediaAccessAlter(AlterableInterface $query): void {
    $this->restrictByUuid($query);
  }

  /**
   * Implements hook_query_TAG_alter() for the 'block_content_access' query tag.
   */
  #[Hook('query_block_content_access_alter')]
  public function queryBlockContentAccessAlter(AlterableInterface $query): void {
    $this->restrictByUuid($query);
  }

  /**
   * Excludes entities with the 'hidden-ENTITY_TYPE_ID' UUID from a query.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   The Views select query to alter.
   */
  public function restrictByUuid(AlterableInterface $query): void {
    if (!($query instanceof SelectInterface)) {
      return;
    }

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $query->getMetaData('view');
    $entity_type = $view->getBaseEntityType();

    $storage = \Drupal::entityTypeManager()->getStorage($entity_type->id());
    if (!($storage instanceof SqlEntityStorageInterface)) {
      return;
    }

    $table_mapping = $storage->getTableMapping();
    if (!($table_mapping instanceof DefaultTableMapping)) {
      return;
    }

    $base_table = $table_mapping->getBaseTable();
    $data_table = $table_mapping->getDataTable();

    // We are excluding entities by UUID, which means we need to be certain the
    // base table is joined in the query.
    $tables = $query->getTables();
    if (isset($tables[$data_table]) && !isset($tables[$base_table])) {
      $data_table_alias = $tables[$data_table]['alias'];
      $id_key = $entity_type->getKey('id');
      $base_table = $query->innerJoin($base_table, NULL, "[$data_table_alias].[$id_key] = [$base_table].[$id_key]");
    }

    // Figure out the column name of the UUID field and add a condition on that.
    $base_field_definitions = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions($entity_type->id());
    $uuid_key = $entity_type->getKey('uuid');
    $uuid_column_name = $table_mapping->getFieldColumnName($base_field_definitions[$uuid_key], NULL);
    $query->condition("$base_table.$uuid_column_name", 'hidden-' . $entity_type->id(), '<>');
  }

}
