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
  $data['comments']['moved to'] = 'comment';
  $data['comment']['comment']['moved to'] = array('field_data_comment_body', 'comment_body');
  $data['comment']['comment']['field']['moved to'] = array('field_data_comment_body', 'comment_body');
  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['comment']['table']['group']  = t('Comment');

  $data['comment']['table']['base'] = array(
    'field' => 'cid',
    'title' => t('Comment'),
    'help' => t("Comments are responses to node content."),
    'access query tag' => 'comment_access',
  );
  $data['comment']['table']['entity type'] = 'comment';

  // Provide a "default relationship" to keep older views from choking.
  $data['comment']['table']['default_relationship'] = array(
    'node' => array(
      'table' => 'node',
      'field' => 'cid',
    ),
  );

  // ----------------------------------------------------------------
  // Fields

  // subject
  $data['comment']['subject'] = array(
    'title' => t('Title'),
    'help' => t('The title of the comment.'),
    'field' => array(
      'handler' => 'views_handler_field_comment',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // cid
  $data['comment']['cid'] = array(
    'title' => t('ID'),
    'help' => t('The comment ID of the field'),
    'field' => array(
      'handler' => 'views_handler_field_comment',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // name (of comment author)
  $data['comment']['name'] = array(
    'title' => t('Author'),
    'help' => t("The name of the comment's author. Can be rendered as a link to the author's homepage."),
    'field' => array(
      'handler' => 'views_handler_field_comment_username',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // homepage
  $data['comment']['homepage'] = array(
    'title' => t("Author's website"),
    'help' => t("The website address of the comment's author. Can be rendered as a link. Will be empty if the author is a registered user."),
    'field' => array(
      'handler' => 'views_handler_field_url',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // hostname
  $data['comment']['hostname'] = array(
    'title' => t('Hostname'),
    'help' => t('Hostname of user that posted the comment.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // mail
  $data['comment']['mail'] = array(
    'title' => t('Mail'),
    'help' => t('Email of user that posted the comment. Will be empty if the author is a registered user.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // created (when comment was posted)
  $data['comment']['created'] = array(
    'title' => t('Post date'),
    'help' => t('Date and time of when the comment was created.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );


  // Language field
  if (module_exists('locale')) {
    $data['comment']['language'] = array(
      'title' => t('Language'),
      'help' => t('The language the comment is in.'),
      'field' => array(
        'handler' => 'views_handler_field_locale_language',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_locale_language',
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_locale_language',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    );
  }

  $data['comments']['timestamp']['moved to'] = array('comment', 'changed');
  // changed (when comment was last updated)
  $data['comment']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('Date and time of when the comment was last updated.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  $data['comments']['timestamp_fulldate']['moved to'] = array('comment', 'changed_fulldata');
  $data['comment']['changed_fulldata'] = array(
    'title' => t('Created date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['comments']['timestamp_year_month']['moved to'] = array('comment', 'changed_year_month');
  $data['comment']['changed_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['comments']['timestamp_year']['moved to'] = array('comment', 'changed_year');
  $data['comment']['changed_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['comments']['timestamp_month']['moved to'] = array('comment', 'changed_month');
  $data['comment']['changed_month'] = array(
    'title' => t('Created month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['comments']['timestamp_day']['moved to'] = array('comment', 'changed_day');
  $data['comment']['changed_day'] = array(
    'title' => t('Created day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['comments']['timestamp_week']['moved to'] = array('comment', 'changed_week');
  $data['comment']['changed_week'] = array(
    'title' => t('Created week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  // status (approved or not)
  $data['comment']['status'] = array(
    'title' => t('Approved'),
    'help' => t('Whether the comment is approved (or still in the moderation queue).'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
      'output formats' => array(
        'approved-not-approved' => array(t('Approved'), t('Not Approved')),
      ),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Approved comment'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // link to view comment
  $data['comment']['view_comment'] = array(
    'field' => array(
      'title' => t('View link'),
      'help' => t('Provide a simple link to view the comment.'),
      'handler' => 'views_handler_field_comment_link',
    ),
  );

  // link to edit comment
  $data['comment']['edit_comment'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the comment.'),
      'handler' => 'views_handler_field_comment_link_edit',
    ),
  );

  // link to delete comment
  $data['comment']['delete_comment'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the comment.'),
      'handler' => 'views_handler_field_comment_link_delete',
    ),
  );


  // link to approve comment
  $data['comment']['approve_comment'] = array(
    'field' => array(
      'title' => t('Approve link'),
      'help' => t('Provide a simple link to approve the comment.'),
      'handler' => 'views_handler_field_comment_link_approve',
    ),
  );

  // link to reply to comment
  $data['comment']['replyto_comment'] = array(
    'field' => array(
      'title' => t('Reply-to link'),
      'help' => t('Provide a simple link to reply to the comment.'),
      'handler' => 'views_handler_field_comment_link_reply',
    ),
  );

  $data['comment']['thread'] = array(
    'field' => array(
      'title' => t('Depth'),
      'help' => t('Display the depth of the comment if it is threaded.'),
      'handler' => 'views_handler_field_comment_depth',
    ),
    'sort' => array(
      'title' => t('Thread'),
      'help' => t('Sort by the threaded order. This will keep child comments together with their parents.'),
      'handler' => 'views_handler_sort_comment_thread',
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
      'handler' => 'views_handler_relationship',
      'label' => t('Content'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
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
      'handler' => 'views_handler_relationship',
      'label' => t('author'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'field' => array(
      'handler' => 'views_handler_field_user',
    ),
  );

  $data['comment']['pid'] = array(
    'title' => t('Parent CID'),
    'help' => t('The Comment ID of the parent comment.'),
    'field' => array(
      'handler' => 'views_handler_field',
    ),
    'relationship' => array(
      'title' => t('Parent comment'),
      'help' => t('The parent comment.'),
      'base' => 'comment',
      'base field' => 'cid',
      'handler' => 'views_handler_relationship',
      'label' => t('Parent comment'),
    ),
  );

  // ----------------------------------------------------------------------
  // node_comment_statistics table

  // define the group
  $data['node_comment_statistics']['table']['group']  = t('Content');

  // joins
  $data['node_comment_statistics']['table']['join'] = array(
    //...to the node table
    'node' => array(
      'type' => 'INNER',
      'left_field' => 'nid',
      'field' => 'nid',
     ),
  );

  // last_comment_timestamp
  $data['node_comment_statistics']['last_comment_timestamp'] = array(
    'title' => t('Last comment time'),
    'help' => t('Date and time of when the last comment was posted.'),
    'field' => array(
      'handler' => 'views_handler_field_last_comment_timestamp',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  // last_comment_name (author's name)
  $data['node_comment_statistics']['last_comment_name'] = array(
    'title' => t("Last comment author"),
    'help' => t('The name of the author of the last posted comment.'),
    'field' => array(
      'handler' => 'views_handler_field_ncs_last_comment_name',
      'click sortable' => TRUE,
      'no group by' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_ncs_last_comment_name',
      'no group by' => TRUE,
    ),
  );

  // comment_count
  $data['node_comment_statistics']['comment_count'] = array(
    'title' => t('Comment count'),
    'help' => t('The number of comments a node has.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
  );

  // last_comment_timestamp
  $data['node_comment_statistics']['last_updated'] = array(
    'title' => t('Updated/commented date'),
    'help' => t('The most recent of last comment posted or node updated time.'),
    'field' => array(
      'handler' => 'views_handler_field_ncs_last_updated',
      'click sortable' => TRUE,
      'no group by' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_ncs_last_updated',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_ncs_last_updated',
    ),
  );

  $data['node_comment_statistics']['cid'] = array(
    'title' => t('Last comment CID'),
    'help' => t('Display the last comment of a node'),
    'relationship' => array(
      'title' => t('Last Comment'),
      'help' => t('The last comment of a node.'),
      'group' => t('Comment'),
      'base' => 'comment',
      'base field' => 'cid',
      'handler' => 'views_handler_relationship',
      'label' => t('Last Comment'),
    ),
  );

  // last_comment_uid
  $data['node_comment_statistics']['last_comment_uid'] = array(
    'title' => t('Last comment uid'),
    'help' => t('The User ID of the author of the last comment of a node.'),
    'relationship' => array(
      'title' => t('Last comment author'),
      'base' => 'users',
      'base field' => 'uid',
      'handler' => 'views_handler_relationship',
      'label' => t('Last comment author'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
    ),
  );

  return $data;
}

/**
 * Use views_data_alter to add items to the node table that are
 * relevant to comments.
 */
function comment_views_data_alter(&$data) {
  // new comments
  $data['node']['new_comments'] = array(
    'title' => t('New comments'),
    'help' => t('The number of new comments on the node.'),
    'field' => array(
      'handler' => 'views_handler_field_node_new_comments',
      'no group by' => TRUE,
    ),
  );

  $data['node']['comments_link'] = array(
    'field' => array(
      'title' => t('Add comment link'),
      'help' => t('Display the standard add comment link used on regular nodes, which will only display if the viewing user has access to add a comment.'),
      'handler' => 'views_handler_field_comment_node_link',
    ),
  );

  // Comment status of the node
  $data['node']['comment'] = array(
    'title' => t('Comment status'),
    'help' => t('Whether comments are enabled or disabled on the node.'),
    'field' => array(
      'handler' => 'views_handler_field_node_comment',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_node_comment',
    ),
  );

  $data['node']['uid_touch'] = array(
    'title' => t('User posted or commented'),
    'help' => t('Display nodes only if a user posted the node or commented on the node.'),
    'argument' => array(
      'field' => 'uid',
      'name table' => 'users',
      'name field' => 'name',
      'handler' => 'views_handler_argument_comment_user_uid',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'field' => 'uid',
      'name table' => 'users',
      'name field' => 'name',
      'handler' => 'views_handler_filter_comment_user_uid'
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
      'handler' => 'views_handler_relationship',
    ),
  );

}

/**
 * Implements hook_views_plugins().
 */
function comment_views_plugins() {
  return array(
    'module' => 'views',
    'row' => array(
      'comment' => array(
        'title' => t('Comment'),
        'help' => t('Display the comment with standard comment view.'),
        'handler' => 'views_plugin_row_comment_view',
        'theme' => 'views_view_row_comment',
        'path' => drupal_get_path('module', 'views') . '/modules/comment', // not necessary for most modules
        'base' => array('comment'), // only works with 'comment' as base.
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-comment',
      ),
      'comment_rss' => array(
        'title' => t('Comment'),
        'help' => t('Display the comment as RSS.'),
        'handler' => 'views_plugin_row_comment_rss',
        'theme' => 'views_view_row_rss',
        'path' => drupal_get_path('module', 'views') . '/modules/comment', // not necessary for most modules
        'base' => array('comment'), // only works with 'comment' as base.
        'uses options' => TRUE,
        'type' => 'feed',
        'help topic' => 'style-comment-rss',
      ),
    ),
  );
}

/**
 * Template helper for theme_views_view_row_comment
 */
function template_preprocess_views_view_row_comment(&$vars) {
  $options = $vars['options'];
  $view = &$vars['view'];
  $plugin = &$view->style_plugin->row_plugin;
  $comment = $plugin->comments[$vars['row']->{$vars['field_alias']}];
  $node = $plugin->nodes[$comment->nid];
  // Put the view on the node so we can retrieve it in the preprocess.
  $node->view = &$view;

  $build = comment_view_multiple(array($comment->cid => $comment), $node, $plugin->options['view_mode']);
  // If we're displaying the comments without links, remove them from the
  // renderable array. There is no way to avoid building them in the first
  // place (see comment_build_content()).
  if (empty($options['links'])) {
    foreach ($build as $cid => &$comment_build) {
      if (isset($comment_build['links'])) {
        unset($comment_build['links']);
      }
    }
  }
  $vars['comment'] = drupal_render($build);
}
