<?php

/**
 * @file
 * Provide views data for comment.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function comment_views_data_alter(&$data) {
  // New comments are only supported for node table because it requires the
  // history table.
  $data['node']['new_comments'] = array(
    'title' => t('New comments'),
    'help' => t('The number of new comments on the node.'),
    'field' => array(
      'id' => 'node_new_comments',
      'no group by' => TRUE,
    ),
  );

  // Provide a integration for each entity type except comment.
  foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type_id == 'comment' || !$entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface') || !$entity_type->getBaseTable()) {
      continue;
    }
    $fields = \Drupal::service('comment.manager')->getFields($entity_type_id);
    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $args = array('@entity_type' => $entity_type_id);

    if ($fields) {
      $data[$base_table]['comments_link'] = array(
        'field' => array(
          'title' => t('Add comment link'),
          'help' => t('Display the standard add comment link used on regular @entity_type, which will only display if the viewing user has access to add a comment.', $args),
          'id' => 'comment_entity_link',
        ),
      );

      // Multilingual properties are stored in data table.
      if (!($table = $entity_type->getDataTable())) {
        $table = $entity_type->getBaseTable();
      }
      $data[$table]['uid_touch'] = array(
        'title' => t('User posted or commented'),
        'help' => t('Display nodes only if a user posted the @entity_type or commented on the @entity_type.', $args),
        'argument' => array(
          'field' => 'uid',
          'name table' => 'users_field_data',
          'name field' => 'name',
          'id' => 'argument_comment_user_uid',
          'no group by' => TRUE,
          'entity_type' => $entity_type_id,
          'entity_id' => $entity_type->getKey('id'),
        ),
        'filter' => array(
          'field' => 'uid',
          'name table' => 'users_field_data',
          'name field' => 'name',
          'id' => 'comment_user_uid',
          'entity_type' => $entity_type_id,
          'entity_id' => $entity_type->getKey('id'),
        ),
      );

      foreach ($fields as $field_name => $field) {
        $data[$base_table][$field_name . '_cid'] = array(
          'title' => t('Comments of the @entity_type using field: @field_name', $args + array('@field_name' => $field_name)),
          'help' => t('Relate all comments on the @entity_type. This will create 1 duplicate record for every comment. Usually if you need this it is better to create a comment view.', $args),
          'relationship' => array(
            'group' => t('Comment'),
            'label' => t('Comments'),
            'base' => 'comment_field_data',
            'base field' => 'entity_id',
            'relationship field' => $entity_type->getKey('id'),
            'id' => 'standard',
            'extra' => array(
              array(
                'field' => 'entity_type',
                'value' => $entity_type_id,
              ),
              array(
                'field' => 'field_name',
                'value' => $field_name,
              ),
            ),
          ),
        );
      }
    }
  }
}
