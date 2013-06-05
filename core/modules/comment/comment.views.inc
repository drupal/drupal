<?php

/**
 * @file
 * Provide views data and handlers for comment.module.
 *
 * @ingroup views_module_handlers
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

  $data['comment']['subject'] = array(
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

  $data['comment']['name'] = array(
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

  $data['comment']['homepage'] = array(
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

  $data['comment']['hostname'] = array(
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

  $data['comment']['mail'] = array(
    'title' => t('E-mail'),
    'help' => t('E-mail of user that posted the comment. Will be empty if the author is a registered user.'),
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

  $data['comment']['created'] = array(
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

  if (module_exists('language')) {
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

  $data['comment']['changed'] = array(
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

  $data['comment']['changed_fulldata'] = array(
    'title' => t('Created date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_fulldate',
    ),
  );

  $data['comment']['changed_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_year_month',
    ),
  );

  $data['comment']['changed_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_year',
    ),
  );

  $data['comment']['changed_month'] = array(
    'title' => t('Created month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_month',
    ),
  );

  $data['comment']['changed_day'] = array(
    'title' => t('Created day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_day',
    ),
  );

  $data['comment']['changed_week'] = array(
    'title' => t('Created week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'id' => 'date_week',
    ),
  );

  $data['comment']['status'] = array(
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

  $data['comment']['thread'] = array(
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

  $data['comment']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('The node ID to which the comment is a reply to.'),
    'relationship' => array(
      'title' => t('Content'),
      'help' => t('The content to which the comment is a reply to.'),
      'base' => 'node',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('Content'),
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

  $data['comment']['uid'] = array(
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

  $data['comment']['pid'] = array(
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

  if (Drupal::moduleHandler()->moduleExists('translation_entity')) {
    $data['comment']['translation_link'] = array(
      'title' => t('Translation link'),
      'help' => t('Provide a link to the translations overview for comments.'),
      'field' => array(
        'id' => 'translation_entity_link',
      ),
    );
  }

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['node_comment_statistics']['table']['group']  = t('Content');

  // Explain how this table joins to others.
  $data['node_comment_statistics']['table']['join'] = array(
    'node' => array(
      'type' => 'INNER',
      'left_field' => 'nid',
      'field' => 'nid',
     ),
  );

  $data['node_comment_statistics']['last_comment_timestamp'] = array(
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

  $data['node_comment_statistics']['last_comment_name'] = array(
    'title' => t("Last comment author"),
    'help' => t('The name of the author of the last posted comment.'),
    'field' => array(
      'id' => 'comment_ncs_last_comment_name',
      'no group by' => TRUE,
    ),
    'sort' => array(
      'id' => 'comment_ncs_last_comment_name',
      'no group by' => TRUE,
    ),
  );

  $data['node_comment_statistics']['comment_count'] = array(
    'title' => t('Comment count'),
    'help' => t('The number of comments a node has.'),
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

  $data['node_comment_statistics']['last_updated'] = array(
    'title' => t('Updated/commented date'),
    'help' => t('The most recent of last comment posted or node updated time.'),
    'field' => array(
      'id' => 'comment_ncs_last_updated',
      'no group by' => TRUE,
    ),
    'sort' => array(
      'id' => 'comment_ncs_last_updated',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'id' => 'comment_ncs_last_updated',
    ),
  );

  $data['node_comment_statistics']['cid'] = array(
    'title' => t('Last comment CID'),
    'help' => t('Display the last comment of a node'),
    'relationship' => array(
      'title' => t('Last comment'),
      'help' => t('The last comment of a node.'),
      'group' => t('Comment'),
      'base' => 'comment',
      'base field' => 'cid',
      'id' => 'standard',
      'label' => t('Last Comment'),
    ),
  );

  $data['node_comment_statistics']['last_comment_uid'] = array(
    'title' => t('Last comment uid'),
    'help' => t('The User ID of the author of the last comment of a node.'),
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

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function comment_views_data_alter(&$data) {
  // new comments
  $data['node']['new_comments'] = array(
    'title' => t('New comments'),
    'help' => t('The number of new comments on the node.'),
    'field' => array(
      'id' => 'node_new_comments',
      'no group by' => TRUE,
    ),
  );

  $data['node']['comments_link'] = array(
    'field' => array(
      'title' => t('Add comment link'),
      'help' => t('Display the standard add comment link used on regular nodes, which will only display if the viewing user has access to add a comment.'),
      'id' => 'comment_node_link',
    ),
  );

  $data['node_field_data']['comment'] = array(
    'title' => t('Comment status'),
    'help' => t('Whether comments are enabled or disabled on the node.'),
    'field' => array(
      'id' => 'node_comment',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'node_comment',
    ),
  );

  $data['node_field_data']['uid_touch'] = array(
    'title' => t('User posted or commented'),
    'help' => t('Display nodes only if a user posted the node or commented on the node.'),
    'argument' => array(
      'field' => 'uid',
      'name table' => 'users',
      'name field' => 'name',
      'id' => 'argument_comment_user_uid',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'field' => 'uid',
      'name table' => 'users',
      'name field' => 'name',
      'id' => 'comment_user_uid',
    ),
  );

  $data['node']['cid'] = array(
    'title' => t('Comments of the node'),
    'help' => t('Relate all comments on the node. This will create 1 duplicate record for every comment. Usually if you need this it is better to create a comment view.'),
    'relationship' => array(
      'group' => t('Comment'),
      'label' => t('Comments'),
      'base' => 'comment',
      'base field' => 'nid',
      'relationship field' => 'nid',
      'id' => 'standard',
    ),
  );

}
