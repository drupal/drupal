<?php

namespace Drupal\comment\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for comment.
 */
class CommentViewsHooks {

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(&$data): void {
    // New comments are only supported for node table because it requires the
    // history table.
    $data['node']['new_comments'] = [
      'title' => t('New comments'),
      'help' => t('The number of new comments on the node.'),
      'field' => [
        'id' => 'node_new_comments',
        'no group by' => TRUE,
      ],
    ];
    // Provides an integration for each entity type except comment.
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type_id == 'comment' || !$entity_type->entityClassImplements(ContentEntityInterface::class) || !$entity_type->getBaseTable()) {
        continue;
      }
      $fields = \Drupal::service('comment.manager')->getFields($entity_type_id);
      $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
      $args = ['@entity_type' => $entity_type_id];
      if ($fields) {
        $data[$base_table]['comments_link'] = [
          'field' => [
            'title' => t('Add comment link'),
            'help' => t('Display the standard add comment link used on regular @entity_type, which will only display if the viewing user has access to add a comment.', $args),
            'id' => 'comment_entity_link',
          ],
        ];
        // Multilingual properties are stored in data table.
        if (!($table = $entity_type->getDataTable())) {
          $table = $entity_type->getBaseTable();
        }
        $data[$table]['uid_touch'] = [
          'title' => t('User posted or commented'),
          'help' => t('Display nodes only if a user posted the @entity_type or commented on the @entity_type.', $args),
          'argument' => [
            'field' => 'uid',
            'name table' => 'users_field_data',
            'name field' => 'name',
            'id' => 'argument_comment_user_uid',
            'no group by' => TRUE,
            'entity_type' => $entity_type_id,
            'entity_id' => $entity_type->getKey('id'),
          ],
          'filter' => [
            'field' => 'uid',
            'name table' => 'users_field_data',
            'name field' => 'name',
            'id' => 'comment_user_uid',
            'entity_type' => $entity_type_id,
            'entity_id' => $entity_type->getKey('id'),
          ],
        ];
        foreach ($fields as $field_name => $field) {
          $data[$base_table][$field_name . '_cid'] = [
            'title' => t('Comments of the @entity_type using field: @field_name', $args + [
              '@field_name' => $field_name,
            ]),
            'help' => t('Relate all comments on the @entity_type. This will create 1 duplicate record for every comment. Usually if you need this it is better to create a comment view.', $args),
            'relationship' => [
              'group' => t('Comment'),
              'label' => t('Comments'),
              'base' => 'comment_field_data',
              'base field' => 'entity_id',
              'relationship field' => $entity_type->getKey('id'),
              'id' => 'standard',
              'extra' => [
                        [
                          'field' => 'entity_type',
                          'value' => $entity_type_id,
                        ],
                        [
                          'field' => 'field_name',
                          'value' => $field_name,
                        ],
              ],
            ],
          ];
        }
      }
    }
  }

}
