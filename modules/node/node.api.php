<?php
// $Id$

/**
 * @file
 * Hooks provided by the Node module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Inform the node access system what permissions the user has.
 *
 * This hook is for implementation by node access modules. In addition to
 * managing access rights for nodes, the node access module must tell
 * the node access system what 'grant IDs' the current user has. In many
 * cases, the grant IDs will simply be role IDs, but grant IDs can be
 * arbitrary based upon the module.
 *
 * For example, modules can maintain their own lists of users, where each
 * list has an ID. In that case, the module could return a list of all
 * IDs of all lists that the current user is a member of.
 *
 * A node access module may implement as many realms as necessary to
 * properly define the access privileges for the nodes.
 *
 * @param $user
 *   The user object whose grants are requested.
 * @param $op
 *   The node operation to be performed, such as "view", "update", or "delete".
 * @return
 *   An array whose keys are "realms" of grants such as "user" or "role", and
 *   whose values are linear lists of grant IDs.
 *
 * For a detailed example, see node_access_example.module.
 *
 * @ingroup node_access
 */
function hook_node_grants($account, $op) {
  if (user_access('access private content', $account)) {
    $grants['example'] = array(1);
  }
  $grants['example_owner'] = array($user->uid);
  return $grants;
}

/**
 * Set permissions for a node to be written to the database.
 *
 * When a node is saved, a module implementing node access will be asked
 * if it is interested in the access permissions to a node. If it is
 * interested, it must respond with an array of array of permissions for that
 * node.
 *
 * Each item in the array should contain:
 *
 * 'realm'
 *    This should only be realms for which the module has returned
 *    grant IDs in hook_node_grants.
 * 'gid'
 *    This is a 'grant ID', which can have an arbitrary meaning per realm.
 * 'grant_view'
 *    If set to TRUE a user with the gid in the realm can view this node.
 * 'grant_edit'
 *    If set to TRUE a user with the gid in the realm can edit this node.
 * 'grant_delete'
 *    If set to TRUE a user with the gid in the realm can delete this node.
 * 'priority'
 *    If multiple modules seek to set permissions on a node, the realms
 *    that have the highest priority will win out, and realms with a lower
 *    priority will not be written. If there is any doubt, it is best to
 *    leave this 0.
 *
 * @ingroup node_access
 */
function hook_node_access_records($node) {
  if (node_access_example_disabling()) {
    return;
  }

  // We only care about the node if it has been marked private. If not, it is
  // treated just like any other node and we completely ignore it.
  if ($node->private) {
    $grants = array();
    $grants[] = array(
      'realm' => 'example',
      'gid' => TRUE,
      'grant_view' => TRUE,
      'grant_update' => FALSE,
      'grant_delete' => FALSE,
      'priority' => 0,
    );

    // For the example_author array, the GID is equivalent to a UID, which
    // means there are many many groups of just 1 user.
    $grants[] = array(
      'realm' => 'example_author',
      'gid' => $node->uid,
      'grant_view' => TRUE,
      'grant_update' => TRUE,
      'grant_delete' => TRUE,
      'priority' => 0,
    );
    return $grants;
  }
}

/**
 * Alter permissions for a node before it is written to the database.
 *
 * Node access modules establish rules for user access to content. Node access
 * records are stored in the {node_access} table and define which permissions
 * are required to access a node. This hook is invoked after node access modules
 * returned their requirements via hook_node_access_records(); doing so allows
 * modules to modify the $grants array by reference before it is stored, so
 * custom or advanced business logic can be applied.
 *
 * @see hook_node_access_records()
 *
 * Upon viewing, editing or deleting a node, hook_node_grants() builds a
 * permissions array that is compared against the stored access records. The
 * user must have one or more matching permissions in order to complete the
 * requested operation.
 *
 * @see hook_node_grants()
 * @see hook_node_grants_alter()
 *
 * @param &$grants
 *   The $grants array returned by hook_node_access_records().
 * @param $node
 *   The node for which the grants were acquired.
 *
 * The preferred use of this hook is in a module that bridges multiple node
 * access modules with a configurable behavior, as shown in the example
 * by the variable 'example_preview_terms'. This variable would
 * be a configuration setting for your module.
 *
 * @ingroup node_access
 */
function hook_node_access_records_alter(&$grants, $node) {
  // Our module allows editors to tag specific articles as 'preview'
  // content using the taxonomy system. If the node being saved
  // contains one of the preview terms defined in our variable
  // 'example_preview_terms', then only our grants are retained,
  // and other grants are removed. Doing so ensures that our rules
  // are enforced no matter what priority other grants are given.
  $preview = variable_get('example_preview_terms', array());
  // Check to see if we have enabled complex behavior.
  if (!empty($preview)) {
    foreach ($preview as $term_id) {
      if (isset($node->taxonomy[$term_id])) {
        // Our module grants are set in $grants['example'].
        $temp = $grants['example'];
        // Now remove all module grants but our own.
        $grants = array('example' => $temp);
        // No need to check additonal terms.
        break;
      }
    }
  }
}

/**
 * Alter user access rules when trying to view, edit or delete a node.
 *
 * Node access modules establish rules for user access to content.
 * hook_node_grants() defines permissions for a user to view, edit or
 * delete nodes by building a $grants array that indicates the permissions
 * assigned to the user by each node access module. This hook is called to allow
 * modules to modify the $grants array by reference, so the interaction of
 * multiple node access modules can be altered or advanced business logic can be
 * applied.
 *
 * @see hook_node_grants()
 *
 * The resulting grants are then checked against the records stored in the
 * {node_access} table to determine if the operation may be completed.
 *
 * @see hook_node_access_records()
 * @see hook_node_access_records_alter()
 *
 * @param &$grants
 *   The $grants array returned by hook_node_grants().
 * @param $account
 *   The user account requesting access to content.
 * @param $op
 *   The operation being performed, 'view', 'update' or 'delete'.
 *
 * Developers may use this hook to either add additional grants to a user
 * or to remove existing grants. These rules are typically based on either the
 * permissions assigned to a user role, or specific attributes of a user
 * account.
 *
 * @ingroup node_access
 */
function hook_node_grants_alter(&$grants, $account, $op) {
  // Our sample module never allows certain roles to edit or delete
  // content. Since some other node access modules might allow this
  // permission, we expressly remove it by returning an empty $grants
  // array for roles specified in our variable setting.

  // Get our list of banned roles.
  $restricted = variable_get('example_restricted_roles', array());

  if ($op != 'view' && !empty($restricted)) {
    // Now check the roles for this account against the restrictions.
    foreach ($restricted as $role_id) {
      if (isset($user->roles[$role_id])) {
        $grants = array();
      }
    }
  }
}

/**
 * Add mass node operations.
 *
 * This hook enables modules to inject custom operations into the mass operations
 * dropdown found at admin/content, by associating a callback function with
 * the operation, which is called when the form is submitted. The callback function
 * receives one initial argument, which is an array of the checked nodes.
 *
 * @return
 *   An array of operations. Each operation is an associative array that may
 *   contain the following key-value pairs:
 *   - "label": Required. The label for the operation, displayed in the dropdown menu.
 *   - "callback": Required. The function to call for the operation.
 *   - "callback arguments": Optional. An array of additional arguments to pass to
 *     the callback function.
 *
 */
function hook_node_operations() {
  $operations = array(
    'approve' => array(
      'label' => t('Approve the selected posts'),
      'callback' => 'node_operations_approve',
    ),
    'promote' => array(
      'label' => t('Promote the selected posts'),
      'callback' => 'node_operations_promote',
    ),
    'sticky' => array(
      'label' => t('Make the selected posts sticky'),
      'callback' => 'node_operations_sticky',
    ),
    'demote' => array(
      'label' => t('Demote the selected posts'),
      'callback' => 'node_operations_demote',
    ),
    'unpublish' => array(
      'label' => t('Unpublish the selected posts'),
      'callback' => 'node_operations_unpublish',
    ),
    'delete' => array(
      'label' => t('Delete the selected posts'),
    ),
  );
  return $operations;
}

/**
 * Act on node deletion.
 *
 * @param $node
 *   The node that is being deleted.
 */
function hook_node_delete($node) {
  db_delete('mytable')
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * A revision of the node is deleted.
 *
 * You can delete data associated with that revision.
 *
 * @param $node
 *   The node the action is being performed on.
 */
function hook_node_revision_delete($node) {
  db_delete('upload')->condition('vid', $node->vid)->execute();
  if (!is_array($node->files)) {
    return;
  }
  foreach ($node->files as $file) {
    file_delete($file);
  }
}

/**
 * Respond to node insertion.
 *
 * Take action when a new node of any type is being inserted in the database.
 *
 * @param $node
 *   The node the action is being performed on.
 */
function hook_node_insert($node) {
  db_insert('mytable')
    ->fields(array(
      'nid' => $node->nid,
      'extra' => $node->extra,
    ))
    ->execute();
}

/**
 * Act on node objects when loaded.
 *
 * This hook allows you to add information to node objects when loaded from
 * the database. It takes an array of nodes indexed by nid as its first
 * parameter. For performance reasons, information for all available nodes
 * should be loaded in a single query where possible.
 *
 * The types of all nodes being passed in are also available in the $types
 * parameter. If your module keeps track of the node types it supports, this
 * allows for an early return if nothing needs to be done.
 *
 * Due to the internal cache in node_load_multiple(), you should not use this
 * hook to modify information returned from the {node} table itself, since
 * this may affect the way nodes are returned from the cache in subsequent
 * calls to the function.
 *
 * @see comment_node_load()
 * @see taxonomy_node_load()
 * @see forum_node_load()
 *
 * @param $nodes
 *   An array of node objects indexed by nid.
 * @param $types
 *   An array containing the types of the nodes.
 */
function hook_node_load($nodes, $types) {
  $result = db_query('SELECT nid, foo FROM {mytable} WHERE nid IN(:nids)', array(':nids' => array_keys($nodes)));
  foreach ($result as $record) {
    $nodes[$record->nid]->foo = $record->foo;
  }
}

/**
 * Control access to a node.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a node.
 *
 * The administrative account (user ID #1) always passes any access check,
 * so this hook is not called in that case. Users with the "bypass node access"
 * permission may always view and edit content through the administrative
 * interface.
 *
 * Note that not all modules will want to influence access on all
 * node types. If your module does not want to actively grant or
 * block access, return NODE_ACCESS_IGNORE or simply return nothing.
 * Blindly returning FALSE will break other node access modules.
 *
 * @link http://api.drupal.org/api/group/node_access/7 More on the node access system @endlink
 * @ingroup node_access
 * @param $node
 *   The node on which the operation is to be performed, or, if it does
 *   not yet exist, the type of node to be created.
 * @param $op
 *   The operation to be performed. Possible values:
 *   - "create"
 *   - "delete"
 *   - "update"
 *   - "view"
 * @param $account
 *   A user object representing the user for whom the operation is to be
 *   performed.
 * @return
 *   NODE_ACCESS_ALLOW if the operation is to be allowed;
 *   NODE_ACCESS_DENY if the operation is to be denied;
 *   NODE_ACCESSS_IGNORE to not affect this operation at all.
 */
function hook_node_access($node, $op, $account) {
  $type = is_string($node) ? $node : (is_array($node) ? $node['type'] : $node->type);

  if (in_array($type, node_permissions_get_configured_types())) {
    if ($op == 'create' && user_access('create ' . $type . ' content', $account)) {
      return NODE_ACCESS_ALLOW;
    }

    if ($op == 'update') {
      if (user_access('edit any ' . $type . ' content', $account) || (user_access('edit own ' . $type . ' content', $account) && ($account->uid == $node->uid))) {
        return NODE_ACCESS_ALLOW;
      }
    }

    if ($op == 'delete') {
      if (user_access('delete any ' . $type . ' content', $account) || (user_access('delete own ' . $type . ' content', $account) && ($account->uid == $node->uid))) {
        return NODE_ACCESS_ALLOW;
      }
    }
  }

  // Returning nothing from this function would have the same effect.
  return NODE_ACCESS_IGNORE;
}


/**
 * The node is about to be shown on the add/edit form.
 *
 * @param $node
 *   The node the action is being performed on.
 */
function hook_node_prepare($node) {
  if (!isset($node->comment)) {
    $node->comment = variable_get("comment_$node->type", COMMENT_NODE_OPEN);
  }
}

/**
 * The node is being cloned for translation.
 *
 * This hook can be used to load additional data or copy values from
 * $node->translation_source.
 *
 * @param $node
 *   The node the action is being performed on.
 */
function hook_node_prepare_translation($node) {
}

/**
 * The node is being displayed as a search result.
 *
 * If you want to display extra information with the result, return it.
 *
 * @param $node
 *   The node the action is being performed on.
 * @return
 *   Extra information to be displayed with search result.
 */
function hook_node_search_result($node) {
  $comments = db_query('SELECT comment_count FROM {node_comment_statistics} WHERE nid = :nid', array('nid' => $node->nid))->fetchField();
  return format_plural($comments, '1 comment', '@count comments');
}

/**
 * The node passed validation and is about to be saved.
 *
 * Modules may make changes to the node before it is saved to the database.
 *
 * @param $node
 *   The node the action is being performed on.
 */
function hook_node_presave($node) {
  if ($node->nid && $node->moderate) {
    // Reset votes when node is updated:
    $node->score = 0;
    $node->users = '';
    $node->votes = 0;
  }
}

/**
 * The node being updated.
 *
 * @param $node
 *   The node the action is being performed on.
 */
function hook_node_update($node) {
  db_update('mytable')
    ->fields(array('extra' => $node->extra))
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * The node is being indexed.
 *
 * If you want additional information to be indexed which is not already
 * visible through node "view", then you should return it here.
 *
 * @param $node
 *   The node the action is being performed on.
 * @return
 *   Array of additional information to be indexed.
 */
function hook_node_update_index($node) {
  $text = '';
  $comments = db_query('SELECT subject, comment, format FROM {comment} WHERE nid = :nid AND status = :status', array(':nid' => $node->nid, ':status' => COMMENT_PUBLISHED));
  foreach ($comments as $comment) {
    $text .= '<h2>' . check_plain($comment->subject) . '</h2>' . check_markup($comment->comment, $comment->format, '', TRUE);
  }
  return $text;
}

/**
 * The user has finished editing the node and is previewing or submitting it.
 *
 * This hook can be used to check the node data. Errors should be set with
 * form_set_error().
 *
 * @param $node
 *   The node the action is being performed on.
 * @param $form
 *   The $form parameter from node_validate().
 */
function hook_node_validate($node, $form) {
  if (isset($node->end) && isset($node->start)) {
    if ($node->start > $node->end) {
      form_set_error('time', t('An event may not end before it starts.'));
    }
  }
}

/**
 * The node content is being assembled before rendering.
 *
 * TODO D7 This needs work to clearly explain the different build modes.
 *
 * The module may add elements to $node->content prior to rendering. This hook
 * will be called after hook_view(). The structure of $node->content is a
 * renderable array as expected by drupal_render().
 *
 * When $build_mode is 'rss', modules can also add extra RSS elements and
 * namespaces to $node->rss_elements and $node->rss_namespaces respectively for
 * the RSS item generated for this node.
 * For details on how this is used @see node_feed()
 *
 * @see taxonomy_node_view()
 * @see upload_node_view()
 * @see comment_node_view()
 *
 * @param $node
 *   The node the action is being performed on.
 * @param $build_mode
 *   The $build_mode parameter from node_build().
 */
function hook_node_view($node, $build_mode) {
  $node->content['my_additional_field'] = array(
    '#value' => $additional_field,
    '#weight' => 10,
    '#theme' => 'mymodule_my_additional_field',
  );
}

/**
 * The node content was built, the module may modify the structured content.
 *
 * This hook is called after the content has been assembled in $node->content
 * and may be used for doing processing which requires that the complete node
 * content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the node rather than the
 * structured content array, it may use this hook to add a #post_render callback.
 * Alternatively, it could also implement hook_preprocess_node(). See
 * drupal_render() and theme() documentation respectively for details.
 *
 * @param $node
 *   The node the action is being performed on.
 * @param $build_mode
 *   The $build_mode parameter from node_build().
 */
function hook_node_build_alter($node, $build_mode) {
  // Check for the existence of a field added by another module.
  if (isset($node->content['an_additional_field'])) {
    // Change its weight.
    $node->content['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the node.
  $node->content['#post_render'][] = 'my_module_node_post_render';
}

/**
 * Defines module-provided node types.
 *
 * This hook allows a module to define one or more of its own node types. For
 * example, the blog module uses it to define a blog node-type named "Blog
 * entry." The name and attributes of each desired node type are specified in
 * an array returned by the hook.
 *
 * Only module-provided node types should be defined through this hook. User-
 * provided (or 'custom') node types should be defined only in the 'node_type'
 * database table, and should be maintained by using the node_type_save() and
 * node_type_delete() functions.
 *
 * @return
 *   An array of information defining the module's node types. The array
 *   contains a sub-array for each node type, with the machine-readable type
 *   name as the key. Each sub-array has up to 10 attributes. Possible
 *   attributes:
 *   - "name": the human-readable name of the node type. Required.
 *   - "base": the base string used to construct callbacks corresponding to
 *      this node type.
 *      (i.e. if base is defined as example_foo, then example_foo_insert will
 *      be called when inserting a node of that type). This string is usually
 *      the name of the module, but not always. Required.
 *   - "description": a brief description of the node type. Required.
 *   - "help": help information shown to the user when creating a node of
 *      this type.. Optional (defaults to '').
 *   - "has_title": boolean indicating whether or not this node type has a title
 *      field. Optional (defaults to TRUE).
 *   - "title_label": the label for the title field of this content type.
 *      Optional (defaults to 'Title').
 *   - "has_body": boolean indicating whether or not this node type has a body
 *      field. Optional (defaults to TRUE).
 *   - "body_label": the label for the body field of this content type. Optional
 *      (defaults to 'Body').
 *   - "locked": boolean indicating whether the administrator can change the
 *      machine name of this type. FALSE = changable (not locked),
 *      TRUE = unchangable (locked). Optional (defaults to TRUE).
 *
 * The machine-readable name of a node type should contain only letters,
 * numbers, and underscores. Underscores will be converted into hyphens for the
 * purpose of contructing URLs.
 *
 * All attributes of a node type that are defined through this hook (except for
 * 'locked') can be edited by a site administrator. This includes the
 * machine-readable name of a node type, if 'locked' is set to FALSE.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_node_info() {
  return array(
    'blog' => array(
      'name' => t('Blog entry'),
      'base' => 'blog',
      'description' => t('Use for multi-user blogs. Every user gets a personal blog.'),
    )
  );
}

/**
 * Provide additional methods of scoring for core search results for nodes.
 *
 * A node's search score is used to rank it among other nodes matched by the
 * search, with the highest-ranked nodes appearing first in the search listing.
 *
 * For example, a module allowing users to vote on content could expose an
 * option to allow search results' rankings to be influenced by the average
 * voting score of a node.
 *
 * All scoring mechanisms are provided as options to site administrators, and
 * may be tweaked based on individual sites or disabled altogether if they do
 * not make sense. Individual scoring mechanisms, if enabled, are assigned a
 * weight from 1 to 10. The weight represents the factor of magnification of
 * the ranking mechanism, with higher-weighted ranking mechanisms having more
 * influence. In order for the weight system to work, each scoring mechanism
 * must return a value between 0 and 1 for every node. That value is then
 * multiplied by the administrator-assigned weight for the ranking mechanism,
 * and then the weighted scores from all ranking mechanisms are added, which
 * brings about the same result as a weighted average.
 *
 * @return
 *   An associative array of ranking data. The keys should be strings,
 *   corresponding to the internal name of the ranking mechanism, such as
 *   'recent', or 'comments'. The values should be arrays themselves, with the
 *   following keys available:
 *   - "title": the human readable name of the ranking mechanism. Required.
 *   - "join": part of a query string to join to any additional necessary
 *     table. This is not necessary if the table required is already joined to
 *     by the base query, such as for the {node} table. Other tables should use
 *     the full table name as an alias to avoid naming collisions. Optional.
 *   - "score": part of a query string to calculate the score for the ranking
 *     mechanism based on values in the database. This does not need to be
 *     wrapped in parentheses, as it will be done automatically; it also does
 *     not need to take the weighted system into account, as it will be done
 *     automatically. It does, however, need to calculate a decimal between
 *     0 and 1; be careful not to cast the entire score to an integer by
 *     inadvertantly introducing a variable argument. Required.
 *   - "arguments": if any arguments are required for the score, they can be
 *     specified in an array here.
 */
function hook_ranking() {
  // If voting is disabled, we can avoid returning the array, no hard feelings.
  if (variable_get('vote_node_enabled', TRUE)) {
    return array(
      'vote_average' => array(
        'title' => t('Average vote'),
        // Note that we use i.sid, the search index's search item id, rather than
        // n.nid.
        'join' => 'LEFT JOIN {vote_node_data} vote_node_data ON vote_node_data.nid = i.sid',
        // The highest possible score should be 1, and the lowest possible score,
        // always 0, should be 0.
        'score' => 'vote_node_data.average / CAST(%f AS DECIMAL)',
        // Pass in the highest possible voting score as a decimal argument.
        'arguments' => array(variable_get('vote_score_max', 5)),
      ),
    );
  }
}


/**
 * Act on node type creation.
 *
 * This hook allows modules to take action when a node type is created.
 *
 * @param $info
 *   The node type object which is being created.
 */
function hook_node_type_insert($info) {
}

/**
 * Act on node type changes.
 *
 * This hook allows modules to take action when a node type is modified.
 *
 * @param $info
 *   The node type object which is being modified.
 */
function hook_node_type_update($info) {
  if (!empty($info->old_type) && $info->old_type != $info->type) {
    $setting = variable_get('comment_' . $info->old_type, COMMENT_NODE_OPEN);
    variable_del('comment_' . $info->old_type);
    variable_set('comment_' . $info->type, $setting);
  }
}

/**
 * Act on node type deletion.
 *
 * This hook allows modules to take action when a node type is deleted.
 *
 * @param $info
 *   The node type object which is being deleted.
 */
function hook_node_type_delete($info) {
  variable_del('comment_' . $info->type);
}

/**
 * Respond to node deletion.
 *
 * This is a hook used by node modules. It is called to allow the module
 * to take action when a node is being deleted from the database by, for
 * example, deleting information from related tables.
 *
 * @param $node
 *   The node being deleted.
 *
 * To take action when nodes of any type are deleted (not just nodes of
 * the type defined by this module), use hook_node() instead.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_delete($node) {
  db_delete('mytable')
    ->condition('nid', $nid->nid)
    ->execute();
}

/**
 * This is a hook used by node modules. It is called after load but before the
 * node is shown on the add/edit form.
 *
 * @param $node
 *   The node being saved.
 *
 * For a usage example, see image.module.
 */
function hook_prepare($node) {
  if ($file = file_check_upload($field_name)) {
    $file = file_save_upload($field_name, _image_filename($file->filename, NULL, TRUE));
    if ($file) {
      if (!image_get_info($file->uri)) {
        form_set_error($field_name, t('Uploaded file is not a valid image'));
        return;
      }
    }
    else {
      return;
    }
    $node->images['_original'] = $file->uri;
    _image_build_derivatives($node, TRUE);
    $node->new_file = TRUE;
  }
}

/**
 * Display a node editing form.
 *
 * This hook, implemented by node modules, is called to retrieve the form
 * that is displayed when one attempts to "create/edit" an item. This form is
 * displayed at the URI http://www.example.com/?q=node/<add|edit>/nodetype.
 *
 * @param $node
 *   The node being added or edited.
 * @param $form_state
 *   The form state array. Changes made to this variable will have no effect.
 * @return
 *   An array containing the form elements to be displayed in the node
 *   edit form.
 *
 * The submit and preview buttons, taxonomy controls, and administrative
 * accoutrements are displayed automatically by node.module. This hook
 * needs to return the node title, the body text area, and fields
 * specific to the node type.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_form($node, $form_state) {
  $type = node_type_get_type($node);

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => check_plain($type->title_label),
    '#required' => TRUE,
  );
  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => check_plain($type->body_label),
    '#rows' => 20,
    '#required' => TRUE,
  );
  $form['field1'] = array(
    '#type' => 'textfield',
    '#title' => t('Custom field'),
    '#default_value' => $node->field1,
    '#maxlength' => 127,
  );
  $form['selectbox'] = array(
    '#type' => 'select',
    '#title' => t('Select box'),
    '#default_value' => $node->selectbox,
    '#options' => array(
      1 => 'Option A',
      2 => 'Option B',
      3 => 'Option C',
    ),
    '#description' => t('Please choose an option.'),
  );

  return $form;
}

/**
 * Respond to node insertion.
 *
 * This is a hook used by node modules. It is called to allow the module
 * to take action when a new node is being inserted in the database by,
 * for example, inserting information into related tables.
 *
 * @param $node
 *   The node being inserted.
 *
 * To take action when nodes of any type are inserted (not just nodes of
 * the type(s) defined by this module), use hook_node() instead.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_insert($node) {
  db_insert('mytable')
    ->fields(array(
      'nid' => $node->nid,
      'extra' => $node->extra,
    ))
    ->execute();
}

/**
 * Load node-type-specific information.
 *
 * This is a hook used by node modules. It is called to allow the module
 * a chance to load extra information that it stores about a node. The hook
 * should not be used to replace information from the core {node} table since
 * this may interfere with the way nodes are fetched from cache.
 *
 * @param $nodes
 *   An array of the nodes being loaded, keyed by nid. At call time,
 *   node.module has already loaded the basic information about the nodes, such
 *   as node ID (nid), title, and body.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_load($nodes) {
  $result = db_query('SELECT nid, foo FROM {mytable} WHERE nid IN (:nids)', array(':nids' => array_keys($nodes)));
  foreach ($result as $record) {
    $nodes[$record->nid]->foo = $record->foo;
  }
}

/**
 * Respond to node updating.
 *
 * This is a hook used by node modules. It is called to allow the module
 * to take action when an edited node is being updated in the database by,
 * for example, updating information in related tables.
 *
 * @param $node
 *   The node being updated.
 *
 * To take action when nodes of any type are updated (not just nodes of
 * the type(s) defined by this module), use hook_node() instead.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_update($node) {
  db_update('mytable')
    ->fields(array('extra' => $node->extra))
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * Verify a node editing form.
 *
 * This is a hook used by node modules. It is called to allow the module
 * to verify that the node is in a format valid to post to the site.
 * Errors should be set with form_set_error().
 *
 * @param $node
 *   The node to be validated.
 * @param $form
 *   The node edit form array.
 *
 * To validate nodes of all types (not just nodes of the type(s) defined by
 * this module), use hook_node() instead.
 *
 * Changes made to the $node object within a hook_validate() function will
 * have no effect. The preferred method to change a node's content is to use
 * hook_node_presave() instead. If it is really necessary to change
 * the node at the validate stage, you can use function form_set_value().
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_validate($node, &$form) {
  if (isset($node->end) && isset($node->start)) {
    if ($node->start > $node->end) {
      form_set_error('time', t('An event may not end before it starts.'));
    }
  }
}

/**
 * Display a node.
 *
 * This is a hook used by node modules. It allows a module to define a
 * custom method of displaying its nodes, usually by displaying extra
 * information particular to that node type.
 *
 * @param $node
 *   The node to be displayed, as returned by node_load().
 * @param $build_mode
 *   Build mode, e.g. 'full', 'teaser', ...
 * @return
 *   $node. The passed $node parameter should be modified as necessary and
 *   returned so it can be properly presented. Nodes are prepared for display
 *   by assembling a structured array, formatted as in the Form API, in
 *   $node->content. As with Form API arrays, the #weight property can be
 *   used to control the relative positions of added elements. After this
 *   hook is invoked, node_build() calls field_attach_view() to add field
 *   views to $node->content, and then invokes hook_node_view() and
 *   hook_node_build_alter(), so if you want to affect the final
 *   view of the node, you might consider implementing one of these hooks
 *   instead.
 *
 * For a detailed usage example, see node_example.module.
 */
function hook_view($node, $build_mode = 'full') {
  if ((bool)menu_get_object()) {
    $breadcrumb = array();
    $breadcrumb[] = array('path' => 'example', 'title' => t('example'));
    $breadcrumb[] = array('path' => 'example/' . $node->field1,
      'title' => t('%category', array('%category' => $node->field1)));
    $breadcrumb[] = array('path' => 'node/' . $node->nid);
    menu_set_location($breadcrumb);
  }

  $node->content['myfield'] = array(
    '#value' => theme('mymodule_myfield', $node->myfield),
    '#weight' => 1,
  );

  return $node;
}

/**
 * @} End of "addtogroup hooks".
 */
