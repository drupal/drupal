<?php

/**
 * @file
 * Provide views data for comment.module.
 */

/**
 * Implements hook_views_data().
 */
function comment_views_data() {

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['comment']['table']['group']  = t('Comment');

  $data['comment']['table']['base'] = array(
    'field' => 'cid',
    'title' => t('Comment'),
    'help' => t("Comments are responses to node content."),
    'access query tag' => 'comment_access',
  );
  $data['comment']['table']['entity type'] = 'comment';
  $data['comment']['table']['wizard_id'] = 'comment';

  $data['comment_field_data']['table']['group'] = t('Comment');
  $data['comment_field_data']['table']['entity type'] = 'comment';
  $data['comment_field_data']['table']['join']['comment'] = array(
    'type' => 'INNER',
    'left_field' => 'cid',
    'field' => 'cid',
  );

  $data['comment_field_data']['subject'] = array(
    'title' => t('Title'),
    'help' => t('The title of the comment.'),
    'field' => array(
      'id' => 'comment',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['comment']['cid'] = array(
    'title' => t('ID'),
    'help' => t('The comment ID of the field'),
    'field' => array(
      'id' => 'comment',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['comment_field_data']['name'] = array(
    'title' => t('Author'),
    'help' => t("The name of the comment's author. Can be rendered as a link to the author's homepage."),
    'field' => array(
      'id' => 'comment_username',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['comment_field_data']['homepage'] = array(
    'title' => t("Author's website"),
    'help' => t("The website address of the comment's author. Can be rendered as a link. Will be empty if the author is a registered user."),
    'field' => array(
      'id' => 'url',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['comment_field_data']['hostname'] = array(
    'title' => t('Hostname'),
    'help' => t('Hostname of user that posted the comment.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['comment_field_data']['mail'] = array(
    'title' => t('Email'),
    'help' => t('Email of user that posted the comment. Will be empty if the author is a registered user.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['comment_field_data']['created'] = array(
    'title' => t('Post date'),
    'help' => t('Date and time of when the comment was created.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  if (\Drupal::moduleHandler()->moduleExists('language')) {
    $data['comment']['langcode'] = array(
      'title' => t('Language'),
      'help' => t('The language the comment is in.'),
      'field' => array(
        'id' => 'language',
      ),
      'filter' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
  }

  $data['comment_field_data']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('Date and time of when the comment was last updated.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['comment_field_data']['changed_fulldata'] = array(
    'title' => t('Created date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_fulldate',
    ),
  );

  $data['comment_field_data']['changed_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_year_month',
    ),
  );

  $data['comment_field_data']['changed_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_year',
    ),
  );

  $data['comment_field_data']['changed_month'] = array(
    'title' => t('Created month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_month',
    ),
  );

  $data['comment_field_data']['changed_day'] = array(
    'title' => t('Created day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_day',
    ),
  );

  $data['comment_field_data']['changed_week'] = array(
    'title' => t('Created week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_week',
    ),
  );

  $data['comment_field_data']['status'] = array(
    'title' => t('Approved status'),
    'help' => t('Whether the comment is approved (or still in the moderation queue).'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'approved-not-approved' => array(t('Approved'), t('Not Approved')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Approved comment status'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['comment']['view_comment'] = array(
    'field' => array(
      'title' => t('Link to comment'),
      'help' => t('Provide a simple link to view the comment.'),
      'id' => 'comment_link',
    ),
  );

  $data['comment']['edit_comment'] = array(
    'field' => array(
      'title' => t('Link to edit comment'),
      'help' => t('Provide a simple link to edit the comment.'),
      'id' => 'comment_link_edit',
    ),
  );

  $data['comment']['delete_comment'] = array(
    'field' => array(
      'title' => t('Link to delete comment'),
      'help' => t('Provide a simple link to delete the comment.'),
      'id' => 'comment_link_delete',
    ),
  );

  $data['comment']['approve_comment'] = array(
    'field' => array(
      'title' => t('Link to approve comment'),
      'help' => t('Provide a simple link to approve the comment.'),
      'id' => 'comment_link_approve',
    ),
  );

  $data['comment']['replyto_comment'] = array(
    'field' => array(
      'title' => t('Link to reply-to comment'),
      'help' => t('Provide a simple link to reply to the comment.'),
      'id' => 'comment_link_reply',
    ),
  );

  $data['comment_field_data']['thread'] = array(
    'field' => array(
      'title' => t('Depth'),
      'help' => t('Display the depth of the comment if it is threaded.'),
      'id' => 'comment_depth',
    ),
    'sort' => array(
      'title' => t('Thread'),
      'help' => t('Sort by the threaded order. This will keep child comments together with their parents.'),
      'id' => 'comment_thread',
    ),
  );

  $data['comment_field_data']['entity_id'] = array(
    'title' => t('Entity ID'),
    'help' => t('The Entity ID to which the comment is a reply to.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['comment_field_data']['entity_type'] = array(
    'title' => t('Entity type'),
    'help' => t('The Entity type to which the comment is a reply to.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['comment_field_data']['field_name'] = array(
    'title' => t('Comment field name'),
    'help' => t('The Field name from which the comment originated.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['comment']['comment_type'] = array(
    'title' => t('Comment type'),
    'help' => t('The comment type for this comment.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $entities_types = \Drupal::entityManager()->getDefinitions();

  // Provide a relationship for each entity type except comment.
  foreach ($entities_types as $type => $entity_type) {
    if ($type == 'comment' || !$entity_type->isFieldable() || !$entity_type->getBaseTable()) {
      continue;
    }
    if ($fields = \Drupal::service('comment.manager')->getFields($type)) {
      $data['comment_field_data'][$type] = array(
        'relationship' => array(
          'title' => $entity_type->getLabel(),
          'help' => t('The @entity_type to which the comment is a reply to.', array('@entity_type' => $entity_type->getLabel())),
          'base' => $entity_type->getBaseTable(),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'entity_id',
          'id' => 'standard',
          'label' => $entity_type->getLabel(),
          'extra' => array(
            array(
              'field' => 'entity_type',
              'value' => $type,
              'table' => 'comment_field_data'
            ),
          ),
        ),
      );
    }
  }

  $data['comment_field_data']['uid'] = array(
    'title' => t('Author uid'),
    'help' => t('If you need more fields than the uid add the comment: author relationship'),
    'relationship' => array(
      'title' => t('Author'),
      'help' => t("The User ID of the comment's author."),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('author'),
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'user',
    ),
  );

  $data['comment_field_data']['pid'] = array(
    'title' => t('Parent CID'),
    'help' => t('The Comment ID of the parent comment.'),
    'field' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'title' => t('Parent comment'),
      'help' => t('The parent comment.'),
      'base' => 'comment',
      'base field' => 'cid',
      'id' => 'standard',
      'label' => t('Parent comment'),
    ),
  );

  if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
    $data['comment']['translation_link'] = array(
      'title' => t('Translation link'),
      'help' => t('Provide a link to the translations overview for comments.'),
      'field' => array(
        'id' => 'content_translation_link',
      ),
    );
  }

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['comment_entity_statistics']['table']['group']  = t('Comment Statistics');

  // Provide a relationship for each entity type except comment.
  foreach ($entities_types as $type => $entity_type) {
    if ($type == 'comment' || !$entity_type->isFieldable() || !$entity_type->getBaseTable()) {
      continue;
    }
    // This relationship does not use the 'field id' column, if the entity has
    // multiple comment-fields, then this might introduce duplicates, in which
    // case the site-builder should enable aggregation and SUM the comment_count
    // field. We cannot create a relationship from the base table to
    // {comment_entity_statistics} for each field as multiple joins between
    // the same two tables is not supported.
    if (\Drupal::service('comment.manager')->getFields($type)) {
      $data['comment_entity_statistics']['table']['join'][$entity_type->getBaseTable()] = array(
        'type' => 'INNER',
        'left_field' => $entity_type->getKey('id'),
        'field' => 'entity_id',
        'extra' => array(
          array(
            'field' => 'entity_type',
            'value' => $type,
          ),
        ),
      );
    }
  }

  $data['comment_entity_statistics']['last_comment_timestamp'] = array(
    'title' => t('Last comment time'),
    'help' => t('Date and time of when the last comment was posted.'),
    'field' => array(
      'id' => 'comment_last_timestamp',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['comment_entity_statistics']['last_comment_name'] = array(
    'title' => t("Last comment author"),
    'help' => t('The name of the author of the last posted comment.'),
    'field' => array(
      'id' => 'comment_ces_last_comment_name',
      'no group by' => TRUE,
    ),
    'sort' => array(
      'id' => 'comment_ces_last_comment_name',
      'no group by' => TRUE,
    ),
  );

  $data['comment_entity_statistics']['comment_count'] = array(
    'title' => t('Comment count'),
    'help' => t('The number of comments an entity has.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
  );

  $data['comment_entity_statistics']['last_updated'] = array(
    'title' => t('Updated/commented date'),
    'help' => t('The most recent of last comment posted or entity updated time.'),
    'field' => array(
      'id' => 'comment_ces_last_updated',
      'no group by' => TRUE,
    ),
    'sort' => array(
      'id' => 'comment_ces_last_updated',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'id' => 'comment_ces_last_updated',
    ),
  );

  $data['comment_entity_statistics']['cid'] = array(
    'title' => t('Last comment CID'),
    'help' => t('Display the last comment of an entity'),
    'relationship' => array(
      'title' => t('Last comment'),
      'help' => t('The last comment of an entity.'),
      'group' => t('Comment'),
      'base' => 'comment',
      'base field' => 'cid',
      'id' => 'standard',
      'label' => t('Last Comment'),
    ),
  );

  $data['comment_entity_statistics']['last_comment_uid'] = array(
    'title' => t('Last comment uid'),
    'help' => t('The User ID of the author of the last comment of an entity.'),
    'relationship' => array(
      'title' => t('Last comment author'),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Last comment author'),
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'numeric',
    ),
  );

  $data['comment_entity_statistics']['entity_type'] = array(
    'title' => t('Entity type'),
    'help' => t('The entity type to which the comment is a reply to.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['comment_entity_statistics']['field_name'] = array(
    'title' => t('Comment field name'),
    'help' => t('The field name from which the comment originated.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}

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
    if ($entity_type_id == 'comment' || !$entity_type->isFieldable() || !$entity_type->getBaseTable()) {
      continue;
    }
    $fields = \Drupal::service('comment.manager')->getFields($entity_type_id);
    $base_table = $entity_type->getBaseTable();
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
          'name table' => 'users',
          'name field' => 'name',
          'id' => 'argument_comment_user_uid',
          'no group by' => TRUE,
          'entity_type' => $entity_type_id,
          'entity_id' => $entity_type->getKey('id'),
        ),
        'filter' => array(
          'field' => 'uid',
          'name table' => 'users',
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
