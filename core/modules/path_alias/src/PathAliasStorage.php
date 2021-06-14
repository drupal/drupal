<?php

namespace Drupal\path_alias;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for path_alias entities.
 */
class PathAliasStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function createWithSampleValues($bundle = FALSE, array $values = []) {
    $entity = parent::createWithSampleValues($bundle, ['path' => '/<front>'] + $values);
    // Ensure the alias is only 255 characters long.
    $entity->set('alias', substr('/' . $entity->get('alias')->value, 0, 255));
    return $entity;
  }

}
