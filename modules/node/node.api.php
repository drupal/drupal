<?php

/**
 * @file
 * Hooks provided by the Node module.
 */

/**
 * @defgroup node_api_hooks Node API Hooks
 * @{
 * Functions to define and modify content types.
 *
 * Each content type is maintained by a primary module, which is either
 * node.module (for content types created in the user interface) or the module
 * that implements hook_node_info() to define the content type.
 *
 * During node operations (create, update, view, delete, etc.), there are
 * several sets of hooks that get invoked to allow modules to modify the base
 * node operation:
 * - Node-type-specific hooks: These hooks are only invoked on the primary
 *   module, using the "base" return component of hook_node_info() as the
 *   function prefix.  For example, poll.module defines the base for the Poll
 *   content type as "poll", so during creation of a poll node, hook_insert() is
 *   only invoked by calling poll_insert().
 * - All-module hooks: This set of hooks is invoked on all implementing modules,
 *   to allow other modules to modify what the primary node module is doing. For
 *   example, hook_node_insert() is invoked on all modules when creating a poll
 *   node.
 * - Field hooks: Hooks related to the fields attached to the node. These are
 *   invoked from the field operations functions described below, and can be
 *   either field-type-specific or all-module hooks.
 * - Entity hooks: Generic hooks for "entity" operations. These are always
 *   invoked on all modules.
 *
 * Here is a list of the node and entity hooks that are invoked, field
 * operations, and other steps that take place during node operations:
 * - Creating a new node (calling node_save() on a new node):
 *   - field_attach_presave()
 *   - hook_node_presave() (all)
 *   - hook_entity_presave() (all)
 *   - Node and revision records are written to the database
 *   - hook_insert() (node-type-specific)
 *   - field_attach_insert()
 *   - hook_node_insert() (all)
 *   - hook_entity_insert() (all)
 *   - hook_node_access_records() (all)
 *   - hook_node_access_records_alter() (all)
 * - Updating an existing node (calling node_save() on an existing node):
 *   - field_attach_presave()
 *   - hook_node_presave() (all)
 *   - hook_entity_presave() (all)
 *   - Node and revision records are written to the database
 *   - hook_update() (node-type-specific)
 *   - field_attach_update()
 *   - hook_node_update() (all)
 *   - hook_entity_update() (all)
 *   - hook_node_access_records() (all)
 *   - hook_node_access_records_alter() (all)
 * - Loading a node (calling node_load(), node_load_multiple() or entity_load()
 *   with $entity_type of 'node'):
 *   - Node and revision information is read from database.
 *   - hook_load() (node-type-specific)
 *   - field_attach_load_revision() and field_attach_load()
 *   - hook_entity_load() (all)
 *   - hook_node_load() (all)
 * - Viewing a single node (calling node_view() - note that the input to
 *   node_view() is a loaded node, so the Loading steps above are already done):
 *   - hook_view() (node-type-specific)
 *   - field_attach_prepare_view()
 *   - hook_entity_prepare_view() (all)
 *   - field_attach_view()
 *   - hook_node_view() (all)
 *   - hook_entity_view() (all)
 *   - hook_node_view_alter() (all)
 *   - hook_entity_view_alter() (all)
 * - Viewing multiple nodes (calling node_view_multiple() - note that the input
 *   to node_view_multiple() is a set of loaded nodes, so the Loading steps
 *   above are already done):
 *   - field_attach_prepare_view()
 *   - hook_entity_prepare_view() (all)
 *   - hook_view() (node-type-specific)
 *   - field_attach_view()
 *   - hook_node_view() (all)
 *   - hook_entity_view() (all)
 *   - hook_node_view_alter() (all)
 *   - hook_entity_view_alter() (all)
 * - Deleting a node (calling node_delete() or node_delete_multiple()):
 *   - Node is loaded (see Loading section above)
 *   - hook_delete() (node-type-specific)
 *   - hook_node_delete() (all)
 *   - hook_entity_delete() (all)
 *   - field_attach_delete()
 *   - Node and revision information are deleted from database
 * - Deleting a node revision (calling node_revision_delete()):
 *   - Node is loaded (see Loading section above)
 *   - Revision information is deleted from database
 *   - hook_node_revision_delete() (all)
 *   - field_attach_delete_revision()
 * - Preparing a node for editing (calling node_form() - note that if it is an
 *   existing node, it will already be loaded; see the Loading section above):
 *   - hook_prepare() (node-type-specific)
 *   - hook_node_prepare() (all)
 *   - hook_form() (node-type-specific)
 *   - field_attach_form()
 * - Validating a node during editing form submit (calling
 *   node_form_validate()):
 *   - hook_validate() (node-type-specific)
 *   - hook_node_validate() (all)
 *   - field_attach_form_validate()
 * - Searching (calling node_search_execute()):
 *   - hook_ranking() (all)
 *   - Query is executed to find matching nodes
 *   - Resulting node is loaded (see Loading section above)
 *   - Resulting node is prepared for viewing (see Viewing a single node above)
 *   - comment_node_update_index() is called.
 *   - hook_node_search_result() (all)
 * - Search indexing (calling node_update_index()):
 *   - Node is loaded (see Loading section above)
 *   - Node is prepared for viewing (see Viewing a single node above)
 *   - hook_node_update_index() (all)
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Inform the node access system what permissions the user has.
 *
 * This hook is for implementation by node access modules. In this hook,
 * the module grants a user different "grant IDs" within one or more
 * "realms". In hook_node_access_records(), the realms and grant IDs are
 * associated with permission to view, edit, and delete individual nodes.
 *
 * The realms and grant IDs can be arbitrarily defined by your node access
 * module; it is common to use role IDs as grant IDs, but that is not required.
 * Your module could instead maintain its own list of users, where each list has
 * an ID. In that case, the return value of this hook would be an array of the
 * list IDs that this user is a member of.
 *
 * A node access module may implement as many realms as necessary to properly
 * define the access privileges for the nodes. Note that the system makes no
 * distinction between published and unpublished nodes. It is the module's
 * responsibility to provide appropriate realms to limit access to unpublished
 * content.
 *
 * Node access records are stored in the {node_access} table and define which
 * grants are required to access a node. There is a special case for the view
 * operation -- a record with node ID 0 corresponds to a "view all" grant for
 * the realm and grant ID of that record. If there are no node access modules
 * enabled, the core node module adds a node ID 0 record for realm 'all'. Node
 * access modules can also grant "view all" permission on their custom realms;
 * for example, a module could create a record in {node_access} with:
 * @code
 * $record = array(
 *   'nid' => 0,
 *   'gid' => 888,
 *   'realm' => 'example_realm',
 *   'grant_view' => 1,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 * );
 * drupal_write_record('node_access', $record);
 * @endcode
 * And then in its hook_node_grants() implementation, it would need to return:
 * @code
 * if ($op == 'view') {
 *   $grants['example_realm'] = array(888);
 * }
 * @endcode
 * If you decide to do this, be aware that the node_access_rebuild() function
 * will erase any node ID 0 entry when it is called, so you will need to make
 * sure to restore your {node_access} record after node_access_rebuild() is
 * called.
 *
 * @see node_access_view_all_nodes()
 * @see node_access_rebuild()
 *
 * @param $account
 *   The user object whose grants are requested.
 * @param $op
 *   The node operation to be performed, such as 'view', 'update', or 'delete'.
 *
 * @return
 *   An array whose keys are "realms" of grants, and whose values are arrays of
 *   the grant IDs within this realm that this user is being granted.
 *
 * For a detailed example, see node_access_example.module.
 *
 * @ingroup node_access
 */
function hook_node_grants($account, $op) {
  if (user_access('access private content', $account)) {
    $grants['example'] = array(1);
  }
  $grants['example_owner'] = array($account->uid);
  return $grants;
}

/**
 * Set permissions for a node to be written to the database.
 *
 * When a node is saved, a module implementing hook_node_access_records() will
 * be asked if it is interested in the access permissions for a node. If it is
 * interested, it must respond with an array of permissions arrays for that
 * node.
 *
 * Node access grants apply regardless of the published or unpublished status
 * of the node. Implementations must make sure not to grant access to
 * unpublished nodes if they don't want to change the standard access control
 * behavior. Your module may need to create a separate access realm to handle
 * access to unpublished nodes.
 *
 * Note that the grant values in the return value from your hook must be
 * integers and not boolean TRUE and FALSE.
 *
 * Each permissions item in the array is an array with the following elements:
 * - 'realm': The name of a realm that the module has defined in
 *   hook_node_grants().
 * - 'gid': A 'grant ID' from hook_node_grants().
 * - 'grant_view': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can view this node. This should usually be
 *   set to $node->status. Failure to do so may expose unpublished content
 *   to some users.
 * - 'grant_update': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can edit this node.
 * - 'grant_delete': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can delete this node.
 * - 'priority': If multiple modules seek to set permissions on a node, the
 *   realms that have the highest priority will win out, and realms with a lower
 *   priority will not be written. If there is any doubt, it is best to
 *   leave this 0.
 *
 *
 * When an implementation is interested in a node but want to deny access to
 * everyone, it may return a "deny all" grant:
 *
 * @code
 * $grants[] = array(
 *   'realm' => 'all',
 *   'gid' => 0,
 *   'grant_view' => 0,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 *   'priority' => 1,
 * );
 * @endcode
 *
 * Setting the priority should cancel out other grants. In the case of a
 * conflict between modules, it is safer to use hook_node_access_records_alter()
 * to return only the deny grant.
 *
 * Note: a deny all grant is not written to the database; denies are implicit.
 *
 * @see node_access_write_grants()
 *
 * @param $node
 *   The node that has just been saved.
 *
 * @return
 *   An array of grants as defined above.
 *
 * @see hook_node_access_records_alter()
 * @ingroup node_access
 */
function hook_node_access_records($node) {
  // We only care about the node if it has been marked private. If not, it is
  // treated just like any other node and we completely ignore it.
  if ($node->private) {
    $grants = array();
    // Only published nodes should be viewable to all users. If we allow access
    // blindly here, then all users could view an unpublished node.
    if ($node->status) {
      $grants[] = array(
        'realm' => 'example',
        'gid' => 1,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'priority' => 0,
      );
    }
    // For the example_author array, the GID is equivalent to a UID, which
    // means there are many groups of just 1 user.
    // Note that an author can always view his or her nodes, even if they
    // have status unpublished.
    $grants[] = array(
      'realm' => 'example_author',
      'gid' => $node->uid,
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
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
 * A module may deny all access to a node by setting $grants to an empty array.
 *
 * @see hook_node_grants()
 * @see hook_node_grants_alter()
 *
 * @param $grants
 *   The $grants array returned by hook_node_access_records().
 * @param $node
 *   The node for which the grants were acquired.
 *
 * The preferred use of this hook is in a module that bridges multiple node
 * access modules with a configurable behavior, as shown in the example with the
 * 'is_preview' field.
 *
 * @ingroup node_access
 */
function hook_node_access_records_alter(&$grants, $node) {
  // Our module allows editors to mark specific articles with the 'is_preview'
  // field. If the node being saved has a TRUE value for that field, then only
  // our grants are retained, and other grants are removed. Doing so ensures
  // that our rules are enforced no matter what priority other grants are given.
  if ($node->is_preview) {
    // Our module grants are set in $grants['example'].
    $temp = $grants['example'];
    // Now remove all module grants but our own.
    $grants = array('example' => $temp);
  }
}

/**
 * Alter user access rules when trying to view, edit or delete a node.
 *
 * Node access modules establish rules for user access to content.
 * hook_node_grants() defines permissions for a user to view, edit or delete
 * nodes by building a $grants array that indicates the permissions assigned to
 * the user by each node access module. This hook is called to allow modules to
 * modify the $grants array by reference, so the interaction of multiple node
 * access modules can be altered or advanced business logic can be applied.
 *
 * @see hook_node_grants()
 *
 * The resulting grants are then checked against the records stored in the
 * {node_access} table to determine if the operation may be completed.
 *
 * A module may deny all access to a user by setting $grants to an empty array.
 *
 * @see hook_node_access_records()
 * @see hook_node_access_records_alter()
 *
 * @param $grants
 *   The $grants array returned by hook_node_grants().
 * @param $account
 *   The user account requesting access to content.
 * @param $op
 *   The operation being performed, 'view', 'update' or 'delete'.
 *
 * Developers may use this hook to either add additional grants to a user or to
 * remove existing grants. These rules are typically based on either the
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
      if (isset($account->roles[$role_id])) {
        $grants = array();
      }
    }
  }
}

/**
 * Add mass node operations.
 *
 * This hook enables modules to inject custom operations into the mass
 * operations dropdown found at admin/content, by associating a callback
 * function with the operation, which is called when the form is submitted. The
 * callback function receives one initial argument, which is an array of the
 * checked nodes.
 *
 * @return
 *   An array of operations. Each operation is an associative array that may
 *   contain the following key-value pairs:
 *   - label: (required) The label for the operation, displayed in the dropdown
 *     menu.
 *   - callback: (required) The function to call for the operation.
 *   - callback arguments: (optional) An array of additional arguments to pass
 *     to the callback function.
 */
function hook_node_operations() {
  $operations = array(
    'publish' => array(
      'label' => t('Publish selected content'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_PUBLISHED)),
    ),
    'unpublish' => array(
      'label' => t('Unpublish selected content'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_NOT_PUBLISHED)),
    ),
    'promote' => array(
      'label' => t('Promote selected content to front page'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_PUBLISHED, 'promote' => NODE_PROMOTED)),
    ),
    'demote' => array(
      'label' => t('Demote selected content from front page'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('promote' => NODE_NOT_PROMOTED)),
    ),
    'sticky' => array(
      'label' => t('Make selected content sticky'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('status' => NODE_PUBLISHED, 'sticky' => NODE_STICKY)),
    ),
    'unsticky' => array(
      'label' => t('Make selected content not sticky'),
      'callback' => 'node_mass_update',
      'callback arguments' => array('updates' => array('sticky' => NODE_NOT_STICKY)),
    ),
    'delete' => array(
      'label' => t('Delete selected content'),
      'callback' => NULL,
    ),
  );
  return $operations;
}

/**
 * Respond to node deletion.
 *
 * This hook is invoked from node_delete_multiple() after the type-specific
 * hook_delete() has been invoked, but before hook_entity_delete and
 * field_attach_delete() are called, and before the node is removed from the
 * node table in the database.
 *
 * @param $node
 *   The node that is being deleted.
 *
 * @ingroup node_api_hooks
 */
function hook_node_delete($node) {
  db_delete('mytable')
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * Respond to deletion of a node revision.
 *
 * This hook is invoked from node_revision_delete() after the revision has been
 * removed from the node_revision table, and before
 * field_attach_delete_revision() is called.
 *
 * @param $node
 *   The node revision (node object) that is being deleted.
 *
 * @ingroup node_api_hooks
 */
function hook_node_revision_delete($node) {
  db_delete('mytable')
    ->condition('vid', $node->vid)
    ->execute();
}

/**
 * Respond to creation of a new node.
 *
 * This hook is invoked from node_save() after the database query that will
 * insert the node into the node table is scheduled for execution, after the
 * type-specific hook_insert() is invoked, and after field_attach_insert() is
 * called.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the save operation is entirely completed
 * and node_save() goes out of scope. You should not rely on data in the
 * database at this time as it is not updated yet. You should also note that any
 * write/update database queries executed from this hook are also not committed
 * immediately. Check node_save() and db_transaction() for more info.
 *
 * @param $node
 *   The node that is being created.
 *
 * @ingroup node_api_hooks
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
 * Act on arbitrary nodes being loaded from the database.
 *
 * This hook should be used to add information that is not in the node or node
 * revisions table, not to replace information that is in these tables (which
 * could interfere with the entity cache). For performance reasons, information
 * for all available nodes should be loaded in a single query where possible.
 *
 * This hook is invoked during node loading, which is handled by entity_load(),
 * via classes NodeController and DrupalDefaultEntityController. After the node
 * information is read from the database or the entity cache, hook_load() is
 * invoked on the node's content type module, then field_attach_load_revision()
 * or field_attach_load() is called, then hook_entity_load() is invoked on all
 * implementing modules, and finally hook_node_load() is invoked on all
 * implementing modules.
 *
 * @param $nodes
 *   An array of the nodes being loaded, keyed by nid.
 * @param $types
 *   An array containing the node types present in $nodes. Allows for an early
 *   return for modules that only support certain node types. However, if your
 *   module defines a content type, you can use hook_load() to respond to
 *   loading of just that content type.
 *
 * For a detailed usage example, see nodeapi_example.module.
 *
 * @ingroup node_api_hooks
 */
function hook_node_load($nodes, $types) {
  // Decide whether any of $types are relevant to our purposes.
  if (count(array_intersect($types_we_want_to_process, $types))) {
    // Gather our extra data for each of these nodes.
    $result = db_query('SELECT nid, foo FROM {mytable} WHERE nid IN(:nids)', array(':nids' => array_keys($nodes)));
    // Add our extra data to the node objects.
    foreach ($result as $record) {
      $nodes[$record->nid]->foo = $record->foo;
    }
  }
}

/**
 * Control access to a node.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a node.
 *
 * The administrative account (user ID #1) always passes any access check, so
 * this hook is not called in that case. Users with the "bypass node access"
 * permission may always view and edit content through the administrative
 * interface.
 *
 * Note that not all modules will want to influence access on all node types. If
 * your module does not want to actively grant or block access, return
 * NODE_ACCESS_IGNORE or simply return nothing. Blindly returning FALSE will
 * break other node access modules.
 *
 * Also note that this function isn't called for node listings (e.g., RSS feeds,
 * the default home page at path 'node', a recent content block, etc.) See
 * @link node_access Node access rights @endlink for a full explanation.
 *
 * @param $node
 *   Either a node object or the machine name of the content type on which to
 *   perform the access check.
 * @param $op
 *   The operation to be performed. Possible values:
 *   - "create"
 *   - "delete"
 *   - "update"
 *   - "view"
 * @param $account
 *   The user object to perform the access check operation on.
 *
 * @return
 *   - NODE_ACCESS_ALLOW: if the operation is to be allowed.
 *   - NODE_ACCESS_DENY: if the operation is to be denied.
 *   - NODE_ACCESS_IGNORE: to not affect this operation at all.
 *
 * @ingroup node_access
 */
function hook_node_access($node, $op, $account) {
  $type = is_string($node) ? $node : $node->type;

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
 * Act on a node object about to be shown on the add/edit form.
 *
 * This hook is invoked from node_object_prepare() after the type-specific
 * hook_prepare() is invoked.
 *
 * @param $node
 *   The node that is about to be shown on the add/edit form.
 *
 * @ingroup node_api_hooks
 */
function hook_node_prepare($node) {
  if (!isset($node->comment)) {
    $node->comment = variable_get("comment_$node->type", COMMENT_NODE_OPEN);
  }
}

/**
 * Act on a node being displayed as a search result.
 *
 * This hook is invoked from node_search_execute(), after node_load() and
 * node_view() have been called.
 *
 * @param $node
 *   The node being displayed in a search result.
 *
 * @return array
 *   Extra information to be displayed with search result. This information
 *   should be presented as an associative array. It will be concatenated with
 *   the post information (last updated, author) in the default search result
 *   theming.
 *
 * @see template_preprocess_search_result()
 * @see search-result.tpl.php
 *
 * @ingroup node_api_hooks
 */
function hook_node_search_result($node) {
  $comments = db_query('SELECT comment_count FROM {node_comment_statistics} WHERE nid = :nid', array('nid' => $node->nid))->fetchField();
  return array('comment' => format_plural($comments, '1 comment', '@count comments'));
}

/**
 * Act on a node being inserted or updated.
 *
 * This hook is invoked from node_save() before the node is saved to the
 * database.
 *
 * @param $node
 *   The node that is being inserted or updated.
 *
 * @ingroup node_api_hooks
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
 * Respond to updates to a node.
 *
 * This hook is invoked from node_save() after the database query that will
 * update node in the node table is scheduled for execution, after the
 * type-specific hook_update() is invoked, and after field_attach_update() is
 * called.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the save operation is entirely completed
 * and node_save() goes out of scope. You should not rely on data in the
 * database at this time as it is not updated yet. You should also note that any
 * write/update database queries executed from this hook are also not committed
 * immediately. Check node_save() and db_transaction() for more info.
 *
 * @param $node
 *   The node that is being updated.
 *
 * @ingroup node_api_hooks
 */
function hook_node_update($node) {
  db_update('mytable')
    ->fields(array('extra' => $node->extra))
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * Act on a node being indexed for searching.
 *
 * This hook is invoked during search indexing, after node_load(), and after the
 * result of node_view() is added as $node->rendered to the node object.
 *
 * @param $node
 *   The node being indexed.
 *
 * @return string
 *   Additional node information to be indexed.
 *
 * @ingroup node_api_hooks
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
 * Perform node validation before a node is created or updated.
 *
 * This hook is invoked from node_validate(), after a user has has finished
 * editing the node and is previewing or submitting it. It is invoked at the
 * end of all the standard validation steps, and after the type-specific
 * hook_validate() is invoked.
 *
 * To indicate a validation error, use form_set_error().
 *
 * Note: Changes made to the $node object within your hook implementation will
 * have no effect.  The preferred method to change a node's content is to use
 * hook_node_presave() instead. If it is really necessary to change the node at
 * the validate stage, you can use form_set_value().
 *
 * @param $node
 *   The node being validated.
 * @param $form
 *   The form being used to edit the node.
 * @param $form_state
 *   The form state array.
 *
 * @ingroup node_api_hooks
 */
function hook_node_validate($node, $form, &$form_state) {
  if (isset($node->end) && isset($node->start)) {
    if ($node->start > $node->end) {
      form_set_error('time', t('An event may not end before it starts.'));
    }
  }
}

/**
 * Act on a node after validated form values have been copied to it.
 *
 * This hook is invoked when a node form is submitted with either the "Save" or
 * "Preview" button, after form values have been copied to the form state's node
 * object, but before the node is saved or previewed. It is a chance for modules
 * to adjust the node's properties from what they are simply after a copy from
 * $form_state['values']. This hook is intended for adjusting non-field-related
 * properties. See hook_field_attach_submit() for customizing field-related
 * properties.
 *
 * @param $node
 *   The node object being updated in response to a form submission.
 * @param $form
 *   The form being used to edit the node.
 * @param $form_state
 *   The form state array.
 *
 * @ingroup node_api_hooks
 */
function hook_node_submit($node, $form, &$form_state) {
  // Decompose the selected menu parent option into 'menu_name' and 'plid', if
  // the form used the default parent selection widget.
  if (!empty($form_state['values']['menu']['parent'])) {
    list($node->menu['menu_name'], $node->menu['plid']) = explode(':', $form_state['values']['menu']['parent']);
  }
}

/**
 * Act on a node that is being assembled before rendering.
 *
 * The module may add elements to $node->content prior to rendering. This hook
 * will be called after hook_view(). The structure of $node->content is a
 * renderable array as expected by drupal_render().
 *
 * When $view_mode is 'rss', modules can also add extra RSS elements and
 * namespaces to $node->rss_elements and $node->rss_namespaces respectively for
 * the RSS item generated for this node.
 * For details on how this is used, see node_feed().
 *
 * @see blog_node_view()
 * @see forum_node_view()
 * @see comment_node_view()
 *
 * @param $node
 *   The node that is being assembled for rendering.
 * @param $view_mode
 *   The $view_mode parameter from node_view().
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_entity_view()
 *
 * @ingroup node_api_hooks
 */
function hook_node_view($node, $view_mode, $langcode) {
  $node->content['my_additional_field'] = array(
    '#markup' => $additional_field,
    '#weight' => 10,
    '#theme' => 'mymodule_my_additional_field',
  );
}

/**
 * Alter the results of node_view().
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * node content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the node rather than the
 * structured content array, it may use this hook to add a #post_render
 * callback.  Alternatively, it could also implement hook_preprocess_node(). See
 * drupal_render() and theme() documentation respectively for details.
 *
 * @param $build
 *   A renderable array representing the node content.
 *
 * @see node_view()
 * @see hook_entity_view_alter()
 *
 * @ingroup node_api_hooks
 */
function hook_node_view_alter(&$build) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the node.
  $build['#post_render'][] = 'my_module_node_post_render';
}

/**
 * Define module-provided node types.
 *
 * This hook allows a module to define one or more of its own node types. For
 * example, the blog module uses it to define a blog node-type named "Blog
 * entry." The name and attributes of each desired node type are specified in an
 * array returned by the hook.
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
 *   - name: (required) The human-readable name of the node type.
 *   - base: (required) The base string used to construct callbacks
 *     corresponding to this node type (for example, if base is defined as
 *     example_foo, then example_foo_insert will be called when inserting a node
 *     of that type). This string is usually the name of the module, but not
 *     always.
 *   - description: (required) A brief description of the node type.
 *   - help: (optional) Help information shown to the user when creating a node
 *     of this type.
 *   - has_title: (optional) A Boolean indicating whether or not this node type
 *     has a title field.
 *   - title_label: (optional) The label for the title field of this content
 *     type.
 *   - locked: (optional) A Boolean indicating whether the administrator can
 *     change the machine name of this type. FALSE = changeable (not locked),
 *     TRUE = unchangeable (locked).
 *
 * The machine name of a node type should contain only letters, numbers, and
 * underscores. Underscores will be converted into hyphens for the purpose of
 * constructing URLs.
 *
 * All attributes of a node type that are defined through this hook (except for
 * 'locked') can be edited by a site administrator. This includes the
 * machine-readable name of a node type, if 'locked' is set to FALSE.
 *
 * @ingroup node_api_hooks
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
 *   - title: (required) The human readable name of the ranking mechanism.
 *   - join: (optional) The part of a query string to join to any additional
 *     necessary table. This is not necessary if the table required is already
 *     joined to by the base query, such as for the {node} table. Other tables
 *     should use the full table name as an alias to avoid naming collisions.
 *   - score: (required) The part of a query string to calculate the score for
 *     the ranking mechanism based on values in the database. This does not need
 *     to be wrapped in parentheses, as it will be done automatically; it also
 *     does not need to take the weighted system into account, as it will be
 *     done automatically. It does, however, need to calculate a decimal between
 *     0 and 1; be careful not to cast the entire score to an integer by
 *     inadvertently introducing a variable argument.
 *   - arguments: (optional) If any arguments are required for the score, they
 *     can be specified in an array here.
 *
 * @ingroup node_api_hooks
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
 * Respond to node type creation.
 *
 * This hook is invoked from node_type_save() after the node type is added to
 * the database.
 *
 * @param $info
 *   The node type object that is being created.
 */
function hook_node_type_insert($info) {
  drupal_set_message(t('You have just created a content type with a machine name %type.', array('%type' => $info->type)));
}

/**
 * Respond to node type updates.
 *
 * This hook is invoked from node_type_save() after the node type is updated in
 * the database.
 *
 * @param $info
 *   The node type object that is being updated.
 */
function hook_node_type_update($info) {
  if (!empty($info->old_type) && $info->old_type != $info->type) {
    $setting = variable_get('comment_' . $info->old_type, COMMENT_NODE_OPEN);
    variable_del('comment_' . $info->old_type);
    variable_set('comment_' . $info->type, $setting);
  }
}

/**
 * Respond to node type deletion.
 *
 * This hook is invoked from node_type_delete() after the node type is removed
 * from the database.
 *
 * @param $info
 *   The node type object that is being deleted.
 */
function hook_node_type_delete($info) {
  variable_del('comment_' . $info->type);
}

/**
 * Respond to node deletion.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_delete() to respond to all node deletions).
 *
 * This hook is invoked from node_delete_multiple() after the node has been
 * removed from the node table in the database, before hook_node_delete() is
 * invoked, and before field_attach_delete() is called.
 *
 * @param $node
 *   The node that is being deleted.
 *
 * @ingroup node_api_hooks
 */
function hook_delete($node) {
  db_delete('mytable')
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * Act on a node object about to be shown on the add/edit form.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_prepare() to act on all node preparations).
 *
 * This hook is invoked from node_object_prepare() before the general
 * hook_node_prepare() is invoked.
 *
 * @param $node
 *   The node that is about to be shown on the add/edit form.
 *
 * @ingroup node_api_hooks
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
 * that is displayed to create or edit a node. This form is displayed at path
 * node/add/[node type] or node/[node ID]/edit.
 *
 * The submit and preview buttons, administrative and display controls, and
 * sections added by other modules (such as path settings, menu settings,
 * comment settings, and fields managed by the Field UI module) are
 * displayed automatically by the node module. This hook just needs to
 * return the node title and form editing fields specific to the node type.
 *
 * @param $node
 *   The node being added or edited.
 * @param $form_state
 *   The form state array.
 *
 * @return
 *   An array containing the title and any custom form elements to be displayed
 *   in the node editing form.
 *
 * @ingroup node_api_hooks
 */
function hook_form($node, &$form_state) {
  $type = node_type_get_type($node);

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => check_plain($type->title_label),
    '#default_value' => !empty($node->title) ? $node->title : '',
    '#required' => TRUE, '#weight' => -5
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
    '#description' => t('Choose an option.'),
  );

  return $form;
}

/**
 * Respond to creation of a new node.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_insert() to act on all node insertions).
 *
 * This hook is invoked from node_save() after the node is inserted into the
 * node table in the database, before field_attach_insert() is called, and
 * before hook_node_insert() is invoked.
 *
 * @param $node
 *   The node that is being created.
 *
 * @ingroup node_api_hooks
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
 * Act on nodes being loaded from the database.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_load() to respond to all node loads).
 *
 * This hook is invoked during node loading, which is handled by entity_load(),
 * via classes NodeController and DrupalDefaultEntityController. After the node
 * information is read from the database or the entity cache, hook_load() is
 * invoked on the node's content type module, then field_attach_node_revision()
 * or field_attach_load() is called, then hook_entity_load() is invoked on all
 * implementing modules, and finally hook_node_load() is invoked on all
 * implementing modules.
 *
 * This hook should only be used to add information that is not in the node or
 * node revisions table, not to replace information that is in these tables
 * (which could interfere with the entity cache). For performance reasons,
 * information for all available nodes should be loaded in a single query where
 * possible.
 *
 * @param $nodes
 *   An array of the nodes being loaded, keyed by nid.
 *
 * For a detailed usage example, see node_example.module.
 *
 * @ingroup node_api_hooks
 */
function hook_load($nodes) {
  $result = db_query('SELECT nid, foo FROM {mytable} WHERE nid IN (:nids)', array(':nids' => array_keys($nodes)));
  foreach ($result as $record) {
    $nodes[$record->nid]->foo = $record->foo;
  }
}

/**
 * Respond to updates to a node.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_update() to act on all node updates).
 *
 * This hook is invoked from node_save() after the node is updated in the
 * node table in the database, before field_attach_update() is called, and
 * before hook_node_update() is invoked.
 *
 * @param $node
 *   The node that is being updated.
 *
 * @ingroup node_api_hooks
 */
function hook_update($node) {
  db_update('mytable')
    ->fields(array('extra' => $node->extra))
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * Perform node validation before a node is created or updated.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_validate() to act on all node validations).
 *
 * This hook is invoked from node_validate(), after a user has finished
 * editing the node and is previewing or submitting it. It is invoked at the end
 * of all the standard validation steps, and before hook_node_validate() is
 * invoked.
 *
 * To indicate a validation error, use form_set_error().
 *
 * Note: Changes made to the $node object within your hook implementation will
 * have no effect.  The preferred method to change a node's content is to use
 * hook_node_presave() instead.
 *
 * @param $node
 *   The node being validated.
 * @param $form
 *   The form being used to edit the node.
 * @param $form_state
 *   The form state array.
 *
 * @ingroup node_api_hooks
 */
function hook_validate($node, $form, &$form_state) {
  if (isset($node->end) && isset($node->start)) {
    if ($node->start > $node->end) {
      form_set_error('time', t('An event may not end before it starts.'));
    }
  }
}

/**
 * Display a node.
 *
 * This hook is invoked only on the module that defines the node's content type
 * (use hook_node_view() to act on all node views).
 *
 * This hook is invoked during node viewing after the node is fully loaded, so
 * that the node type module can define a custom method for display, or add to
 * the default display.
 *
 * @param $node
 *   The node to be displayed, as returned by node_load().
 * @param $view_mode
 *   View mode, e.g. 'full', 'teaser', ...
 * @return
 *   The passed $node parameter should be modified as necessary and returned so
 *   it can be properly presented. Nodes are prepared for display by assembling
 *   a structured array, formatted as in the Form API, in $node->content. As
 *   with Form API arrays, the #weight property can be used to control the
 *   relative positions of added elements. After this hook is invoked,
 *   node_view() calls field_attach_view() to add field views to $node->content,
 *   and then invokes hook_node_view() and hook_node_view_alter(), so if you
 *   want to affect the final view of the node, you might consider implementing
 *   one of these hooks instead.
 *
 * @ingroup node_api_hooks
 */
function hook_view($node, $view_mode) {
  if ($view_mode == 'full' && node_is_page($node)) {
    $breadcrumb = array();
    $breadcrumb[] = l(t('Home'), NULL);
    $breadcrumb[] = l(t('Example'), 'example');
    $breadcrumb[] = l($node->field1, 'example/' . $node->field1);
    drupal_set_breadcrumb($breadcrumb);
  }

  $node->content['myfield'] = array(
    '#markup' => theme('mymodule_myfield', $node->myfield),
    '#weight' => 1,
  );

  return $node;
}

/**
 * @} End of "addtogroup hooks".
 */
