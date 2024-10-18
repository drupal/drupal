<?php

namespace Drupal\path_alias;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the path_alias schema handler.
 */
class PathAliasStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    $base_table = $this->storage->getBaseTable();
    $revision_table = $this->storage->getRevisionTable();

    $schema[$base_table]['indexes'] += [
      'path_alias__alias_langcode_id_status' => ['alias', 'langcode', 'id', 'status'],
      'path_alias__path_langcode_id_status' => ['path', 'langcode', 'id', 'status'],
    ];
    $schema[$revision_table]['indexes'] += [
      'path_alias_revision__alias_langcode_id_status' => ['alias', 'langcode', 'id', 'status'],
      'path_alias_revision__path_langcode_id_status' => ['path', 'langcode', 'id', 'status'],
    ];

    // Unset the path_alias__status index as it is slower than the above
    // indexes and MySQL 5.7 chooses to use it even though it is suboptimal.
    unset($schema[$base_table]['indexes']['path_alias__status']);

    return $schema;
  }

}
