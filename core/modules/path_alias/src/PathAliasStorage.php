<?php

namespace Drupal\path_alias;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for path_alias entities.
 */
class PathAliasStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    parent::invokeHook($hook, $entity);

    // Invoke the deprecated hook_path_OPERATION() hooks.
    if ($hook === 'insert' || $hook === 'update' || $hook === 'delete') {
      $values = [
        'pid' => $entity->id(),
        'source' => $entity->getPath(),
        'alias' => $entity->getAlias(),
        'langcode' => $entity->language()->getId(),
      ];

      if ($hook === 'update') {
        $values['original'] = [
          'pid' => $entity->id(),
          'source' => $entity->original->getPath(),
          'alias' => $entity->original->getAlias(),
          'langcode' => $entity->original->language()->getId(),
        ];
      }

      $this->moduleHandler()->invokeAllDeprecated("It will be removed before Drupal 9.0.0. Use hook_ENTITY_TYPE_{$hook}() for the 'path_alias' entity type instead. See https://www.drupal.org/node/3013865.", 'path_' . $hook, [$values]);
    }
  }

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
