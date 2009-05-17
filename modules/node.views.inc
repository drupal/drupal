<?php

/**
 * @file
 * Provide views data and handlers for node.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function node_views_data() {
  // ----------------------------------------------------------------
  // node table -- basic table information.

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['node']['table']['group'] = t('Content');

  // Advertise this table as a possible base table
  $data['node']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('Content'),
    'weight' => -10,
    'access query tag' => 'node_access',
    'defaults' => array(
      'field' => 'title',
    ),
  );
  $data['node']['table']['entity type'] = 'node';


  // For other base tables, explain how we join
  $data['node']['table']['join'] = array(
    // this explains how the 'node' table (named in the line above)
    // links toward the node_revision table.
    'node_revision' => array(
      'handler' => 'views_join', // this is actually optional
      'left_table' => 'node_revision', // Because this is a direct link it could be left out.
      'left_field' => 'nid',
      'field' => 'nid',
      // also supported:
      // 'type' => 'INNER',
      // 'extra' => array(array('field' => 'fieldname', 'value' => 'value', 'operator' => '='))
      //   Unfortunately, you can't specify other tables here, but you can construct
      //   alternative joins in the handlers that can do that.
      // 'table' => 'the actual name of this table in the database',
     ),
  );

  // ----------------------------------------------------------------
  // node table -- fields

  // nid
  $data['node']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('The node ID.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'handler' => 'views_handler_field_node',
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as an argument
    'argument' => array(
      'handler' => 'views_handler_argument_node_nid',
      'name field' => 'title', // the field to display in the summary.
      'numeric' => TRUE,
      'validate type' => 'nid',
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // title
  // This definition has more items in it than it needs to as an example.
  $data['node']['title'] = array(
    'title' => t('Title'), // The item it appears as on the UI,
    'help' => t('The content title.'), // The help that appears on the UI,
     // Information for displaying a title as a field
    'field' => array(
      'field' => 'title', // the real field. This could be left out since it is the same.
      'group' => t('Content'), // The group it appears in on the UI. Could be left out.
      'handler' => 'views_handler_field_node',
      'click sortable' => TRUE,
      'link_to_node default' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // created field
  $data['node']['created'] = array(
    'title' => t('Post date'), // The item it appears as on the UI,
    'help' => t('The date the content was posted.'), // The help that appears on the UI,
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

  // changed field
  $data['node']['changed'] = array(
    'title' => t('Updated date'), // The item it appears as on the UI,
    'help' => t('The date the content was last updated.'), // The help that appears on the UI,
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

  // Content type
  $data['node']['type'] = array(
    'title' => t('Type'), // The item it appears as on the UI,
    'help' => t('The content type (for example, "blog entry", "forum post", "story", etc).'), // The help that appears on the UI,
    'field' => array(
      'handler' => 'views_handler_field_node_type',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_node_type',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_node_type',
    ),
  );

  // published status
  $data['node']['status'] = array(
    'title' => t('Published'),
    'help' => t('Whether or not the content is published.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
      'output formats' => array(
        'published-notpublished' => array(t('Published'), t('Not published')),
      ),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Published'),
      'type' => 'yes-no',
      'use equal' => TRUE, // Use status = 1 instead of status <> 0 in WHERE statment
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // published status + extra
  $data['node']['status_extra'] = array(
    'title' => t('Published or admin'),
    'help' => t('Filters out unpublished content if the current user cannot view it.'),
    'filter' => array(
      'field' => 'status',
      'handler' => 'views_handler_filter_node_status',
      'label' => t('Published or admin'),
    ),
  );

  // promote status
  $data['node']['promote'] = array(
    'title' => t('Promoted to front page'),
    'help' => t('Whether or not the content is promoted to the front page.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
      'output formats' => array(
        'promoted-notpromoted' => array(t('Promoted'), t('Not promoted')),
      ),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Promoted to front page'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // sticky
  $data['node']['sticky'] = array(
    'title' => t('Sticky'), // The item it appears as on the UI,
    'help' => t('Whether or not the content is sticky.'), // The help that appears on the UI,
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
      'output formats' => array(
        'sticky' => array(t('Sticky'), t('Not sticky')),
      ),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Sticky'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
      'help' => t('Whether or not the content is sticky. To list sticky content first, set this to descending.'),
    ),
  );

  // Define some fields based upon views_handler_field_entity in the entity
  // table so they can be re-used with other query backends.
  // @see views_handler_field_entity

  $data['node']['view_node']['moved to'] = array('views_entity_node', 'view_node');
  $data['views_entity_node']['view_node'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a simple link to the content.'),
      'handler' => 'views_handler_field_node_link',
    ),
  );

  $data['node']['edit_node']['moved to'] = array('views_entity_node', 'edit_node');
  $data['views_entity_node']['edit_node'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the content.'),
      'handler' => 'views_handler_field_node_link_edit',
    ),
  );

  $data['node']['delete_node']['moved to'] = array('views_entity_node', 'delete_node');
  $data['views_entity_node']['delete_node'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the content.'),
      'handler' => 'views_handler_field_node_link_delete',
    ),
  );

  $data['node']['path'] = array(
    'field' => array(
      'title' => t('Path'),
      'help' => t('The aliased path to this content.'),
      'handler' => 'views_handler_field_node_path',
    ),
  );


  // Bogus fields for aliasing purposes.

  $data['node']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['node']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['node']['created_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['node']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['node']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['node']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  $data['node']['changed_fulldate'] = array(
    'title' => t('Updated date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_fulldate',
    ),
  );

  $data['node']['changed_year_month'] = array(
    'title' => t('Updated year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year_month',
    ),
  );

  $data['node']['changed_year'] = array(
    'title' => t('Updated year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_year',
    ),
  );

  $data['node']['changed_month'] = array(
    'title' => t('Updated month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_month',
    ),
  );

  $data['node']['changed_day'] = array(
    'title' => t('Updated day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_day',
    ),
  );

  $data['node']['changed_week'] = array(
    'title' => t('Updated week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'changed',
      'handler' => 'views_handler_argument_node_created_week',
    ),
  );

  // uid field
  $data['node']['uid'] = array(
    'title' => t('Author uid'),
    'help' => t('The user authoring the content. If you need more fields than the uid add the content: author relationship'),
    'relationship' => array(
      'title' => t('Author'),
      'help' => t('Relate content to the user who created it.'),
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('author'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_user_name',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'field' => array(
      'handler' => 'views_handler_field_user',
    ),
  );

  $data['node']['uid_revision'] = array(
    'title' => t('User has a revision'),
    'help' => t('All nodes where a certain user has a revision'),
    'real field' => 'nid',
    'filter' => array(
      'handler' => 'views_handler_filter_node_uid_revision',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_node_uid_revision',
    ),
  );

  // ----------------------------------------------------------------------
  // Content revision table

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['node_revisions']['moved to'] = 'node_revision';
  $data['node_revision']['table']['entity type'] = 'node';
  $data['node_revision']['table']['group']  = t('Content revision');
  // Support the conversion of the field body
  $data['node_revisions']['body']['moved to'] = array('field_revision_data', 'body-revision_id');

  // Advertise this table as a possible base table
  $data['node_revision']['table']['base'] = array(
    'field' => 'vid',
    'title' => t('Content revision'),
    'help' => t('Content revision is a history of changes to content.'),
    'defaults' => array(
      'field' => 'title',
    ),
  );

  // For other base tables, explain how we join
  $data['node_revision']['table']['join'] = array(
    // Directly links to node table.
    'node' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
  );

  // uid field for node revision
  $data['node_revision']['uid'] = array(
    'title' => t('User'),
    'help' => t('Relate a content revision to the user who created the revision.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
      'label' => t('revision user'),
    ),
  );

  // nid
  $data['node_revision']['vid'] = array(
    'title' => t('Vid'),
    'help' => t('The revision ID of the content revision.'), // The help that appears on the UI,
    // Information for displaying the nid
    'field' => array(
      'click sortable' => TRUE,
    ),
    // Information for accepting a nid as an argument
    'argument' => array(
      'handler' => 'views_handler_argument_node_vid',
      'click sortable' => TRUE,
      'numeric' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'node',
      'base field' => 'vid',
      'title' => t('Content'),
      'label' => t('Get the actual content from a content revision.'),
    ),
  );

  // title
  $data['node_revision']['title'] = array(
    'title' => t('Title'), // The item it appears as on the UI,
    'help' => t('The content title.'), // The help that appears on the UI,
     // Information for displaying a title as a field
    'field' => array(
      'field' => 'title', // the real field
      'handler' => 'views_handler_field_node_revision',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // log field
  $data['node_revision']['log'] = array(
    'title' => t('Log message'), // The item it appears as on the UI,
    'help' => t('The log message entered when the revision was created.'), // The help that appears on the UI,
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_xss',
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // revision timestamp
  // changed field
  $data['node_revision']['timestamp'] = array(
    'title' => t('Updated date'), // The item it appears as on the UI,
    'help' => t('The date the node was last updated.'), // The help that appears on the UI,
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

  $data['node_revision']['link_to_revision'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a simple link to the revision.'),
      'handler' => 'views_handler_field_node_revision_link',
    ),
  );

  $data['node_revision']['revert_revision'] = array(
    'field' => array(
      'title' => t('Revert link'),
      'help' => t('Provide a simple link to revert to the revision.'),
      'handler' => 'views_handler_field_node_revision_link_revert',
    ),
  );

  $data['node_revision']['delete_revision'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the content revision.'),
      'handler' => 'views_handler_field_node_revision_link_delete',
    ),
  );

  // ----------------------------------------------------------------------
  // Node access table

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['node_access']['table']['group']  = t('Content access');

  // For other base tables, explain how we join
  $data['node_access']['table']['join'] = array(
    // Directly links to node table.
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  // nid field
  $data['node_access']['nid'] = array(
    'title' => t('Access'),
    'help' => t('Filter by access.'),
    'filter' => array(
      'handler' => 'views_handler_filter_node_access',
      'help' => t('Filter for content by view access. <strong>Not necessary if you are using node as your base table.</strong>'),
    ),
  );

  // ----------------------------------------------------------------------
  // History table

  // We're actually defining a specific instance of the table, so let's
  // alias it so that we can later add the real table for other purposes if we
  // need it.
  $data['history_user']['moved to'] = 'history';
  $data['history']['table']['group']  = t('Content');

  // Explain how this table joins to others.
  $data['history']['table']['join'] = array(
     // Directly links to node table.
    'node' => array(
      'table' => 'history',
      'left_field' => 'nid',
      'field' => 'nid',
      'extra' => array(
        array('field' => 'uid', 'value' => '***CURRENT_USER***', 'numeric' => TRUE),
      ),
    ),
  );

  $data['history']['timestamp'] = array(
    'title' => t('Has new content'),
    'field' => array(
      'handler' => 'views_handler_field_history_user_timestamp',
      'help' => t('Show a marker if the content is new or updated.'),
    ),
    'filter' => array(
      'help' => t('Show only content that is new or updated.'),
      'handler' => 'views_handler_filter_history_user_timestamp',
    ),
  );
  return $data;
}

/**
 * Implements hook_views_plugins().
 */
function node_views_plugins() {
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'row' => array(
      'node' => array(
        'title' => t('Content'),
        'help' => t('Display the content with standard node view.'),
        'handler' => 'views_plugin_row_node_view',
        'path' => drupal_get_path('module', 'views') . '/modules/node', // not necessary for most modules
        'base' => array('node'), // only works with 'node' as base.
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-node',
      ),
      'node_rss' => array(
        'title' => t('Content'),
        'help' => t('Display the content with standard node view.'),
        'handler' => 'views_plugin_row_node_rss',
        'path' => drupal_get_path('module', 'views') . '/modules/node', // not necessary for most modules
        'theme' => 'views_view_row_rss',
        'base' => array('node'), // only works with 'node' as base.
        'uses options' => TRUE,
        'type' => 'feed',
        'help topic' => 'style-node-rss',
      ),
    ),
    'argument validator' => array(
      'node' => array(
        'title' => t('Content'),
        'handler' => 'views_plugin_argument_validate_node',
      ),
    ),
    'argument default' => array(
      'node' => array(
        'title' => t('Content ID from URL'),
        'handler' => 'views_plugin_argument_default_node'
      ),
    ),
  );
}

/**
 * Implements hook_preprocess_node().
 */
function node_row_node_view_preprocess_node(&$vars) {
  $node = $vars['node'];
  $options = $vars['view']->style_plugin->row_plugin->options;

  // Prevent the comment form from showing up if this is not a page display.
  if ($vars['view_mode'] == 'full' && !$vars['view']->display_handler->has_path()) {
    $node->comment = FALSE;
  }

  if (!$options['links']) {
    unset($vars['content']['links']);
  }

  if (!empty($options['comments']) && user_access('access comments') && $node->comment) {
    $vars['content']['comments'] = comment_node_page_additions($node);
  }
}

/**
 * Implements hook_views_query_substitutions().
 */
function node_views_query_substitutions() {
  return array(
    '***ADMINISTER_NODES***' => intval(user_access('administer nodes')),
    '***VIEW_OWN_UNPUBLISHED_NODES***' => intval(user_access('view own unpublished content')),
    '***BYPASS_NODE_ACCESS***' =>  intval(user_access('bypass node access')),
  );
}

/**
 * Implements hook_views_analyze().
 */
function node_views_analyze($view) {
  $ret = array();
  // Check for something other than the default display:
  if ($view->base_table == 'node') {
    foreach ($view->display as $id => $display) {
      if (empty($display->handler)) {
        continue;
      }
      if (!$display->handler->is_defaulted('access') || !$display->handler->is_defaulted('filters')) {
        // check for no access control
        $access = $display->handler->get_option('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $select = db_select('role', 'r');
          $select->innerJoin('role_permission', 'p', 'r.rid = p.rid');
          $result = $select->fields('r', array('name'))
            ->fields('p', array('permission'))
            ->condition('r.name', array('anonymous user', 'authenticated user'), 'IN')
            ->condition('p.permission', 'access content')
            ->execute();

          foreach ($result as $role) {
            $role->safe = TRUE;
            $roles[$role->name] = $role;
          }
          if (!($roles['anonymous user']->safe && $roles['authenticated user']->safe)) {
            $ret[] = views_ui_analysis(t('Some roles lack permission to access content, but display %display has no access control.', array('%display' => $display->display_title)), 'warning');
          }
          $filters = $display->handler->get_option('filters');
          foreach ($filters as $filter) {
            if ($filter['table'] == 'node' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
              continue 2;
            }
          }
          $ret[] = views_ui_analysis(t('Display %display has no access control but does not contain a filter for published nodes.', array('%display' => $display->display_title)), 'warning');
        }
      }
    }
  }
  foreach ($view->display as $id => $display) {
    if ($display->display_plugin == 'page') {
      if ($display->handler->get_option('path') == 'node/%') {
        $ret[] = views_ui_analysis(t('Display %display has set node/% as path. This will not produce what you want. If you want to have multiple versions of the node view, use panels.', array('%display' => $display->display_title)), 'warning');
      }
    }
  }

  return $ret;
}
