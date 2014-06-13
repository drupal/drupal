<?php

use Drupal\node\NodeInterface;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;

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
 * that defines the content type by providing configuration file.
 *
 * During node operations (create, insert, update, view, delete, etc.), there
 * are several sets of hooks that get invoked to allow modules to modify the
 * base node operation:
 * - All-module hooks: This set of hooks is invoked on all implementing modules,
 *   to allow other modules to modify what the primary node module is doing. For
 *   example, hook_node_insert() is invoked on all modules when creating a forum
 *   node.
 * - Field hooks: Hooks related to the fields attached to the node. These are
 *   invoked from the field operations functions described below, and can be
 *   either field-type-specific or all-module hooks.
 * - Entity hooks: Generic hooks for "entity" operations. These are always
 *   invoked on all modules.
 *
 * Here is a list of the node and entity hooks that are invoked, and other
 * steps that take place during node operations:
 * - Instantiating a new node:
 *   - hook_node_create() (all)
 *   - hook_entity_create() (all)
 * - Creating a new node (calling $node->save() on a new node):
 *   - hook_node_presave() (all)
 *   - hook_entity_presave() (all)
 *   - Node and revision records are written to the database
 *   - hook_node_insert() (all)
 *   - hook_entity_insert() (all)
 *   - hook_node_access_records() (all)
 *   - hook_node_access_records_alter() (all)
 * - Updating an existing node (calling $node->save() on an existing node):
 *   - hook_node_presave() (all)
 *   - hook_entity_presave() (all)
 *   - Node and revision records are written to the database
 *   - hook_node_update() (all)
 *   - hook_entity_update() (all)
 *   - hook_node_access_records() (all)
 *   - hook_node_access_records_alter() (all)
 * - Loading a node (calling node_load(), node_load_multiple(), entity_load(),
 *   or entity_load_multiple() with $entity_type of 'node'):
 *   - Node and revision information is read from database.
 *   - hook_entity_load() (all)
 *   - hook_node_load() (all)
 * - Viewing a single node (calling node_view() - note that the input to
 *   node_view() is a loaded node, so the Loading steps above are already done):
 *   - hook_entity_prepare_view() (all)
 *   - hook_entity_display_build_alter() (all)
 *   - hook_node_view() (all)
 *   - hook_entity_view() (all)
 *   - hook_node_view_alter() (all)
 *   - hook_entity_view_alter() (all)
 * - Viewing multiple nodes (calling node_view_multiple() - note that the input
 *   to node_view_multiple() is a set of loaded nodes, so the Loading steps
 *   above are already done):
 *   - hook_entity_prepare_view() (all)
 *   - hook_entity_display_build_alter() (all)
 *   - hook_node_view() (all)
 *   - hook_entity_view() (all)
 *   - hook_node_view_alter() (all)
 *   - hook_entity_view_alter() (all)
 * - Deleting a node (calling $node->delete() or entity_delete_multiple()):
 *   - Node is loaded (see Loading section above)
 *   - hook_node_predelete() (all)
 *   - hook_entity_predelete() (all)
 *   - Node and revision information are deleted from database
 *   - hook_node_delete() (all)
 *   - hook_entity_delete() (all)
 * - Deleting a node revision (calling node_revision_delete()):
 *   - Node is loaded (see Loading section above)
 *   - Revision information is deleted from database
 *   - hook_node_revision_delete() (all)
 * - Preparing a node for editing (calling node_form() - note that if it is an
 *   existing node, it will already be loaded; see the Loading section above):
 *   - hook_node_prepare_form() (all)
 *   - hook_entity_prepare_form() (all)
 *   - @todo hook for EntityFormDisplay::buildForm()
 * - Validating a node during editing form submit (calling
 *   node_form_validate()):
 *   - hook_node_validate() (all)
 * - Searching (using the 'node_search' plugin):
 *   - hook_ranking() (all)
 *   - Query is executed to find matching nodes
 *   - Resulting node is loaded (see Loading section above)
 *   - Resulting node is prepared for viewing (see Viewing a single node above)
 *   - comment_node_update_index() is called (this adds "N comments" text)
 *   - hook_node_search_result() (all)
 * - Search indexing (calling updateIndex() on the 'node_search' plugin):
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
 * db_insert('node_access')->fields($record)->execute();
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
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The acccount object whose grants are requested.
 * @param string $op
 *   The node operation to be performed, such as 'view', 'update', or 'delete'.
 *
 * @return array
 *   An array whose keys are "realms" of grants, and whose values are arrays of
 *   the grant IDs within this realm that this user is being granted.
 *
 * For a detailed example, see node_access_example.module.
 *
 * @see node_access_view_all_nodes()
 * @see node_access_rebuild()
 * @ingroup node_access
 */
function hook_node_grants(\Drupal\Core\Session\AccountInterface $account, $op) {
  if (user_access('access private content', $account)) {
    $grants['example'] = array(1);
  }
  $grants['example_owner'] = array($account->id());
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
 *   set to $node->isPublished(). Failure to do so may expose unpublished content
 *   to some users.
 * - 'grant_update': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can edit this node.
 * - 'grant_delete': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can delete this node.
 * - langcode: (optional) The language code of a specific translation of the
 *   node, if any. Modules may add this key to grant different access to
 *   different translations of a node, such that (e.g.) a particular group is
 *   granted access to edit the Catalan version of the node, but not the
 *   Hungarian version. If no value is provided, the langcode is set
 *   automatically from the $node parameter and the node's original language (if
 *   specified) is used as a fallback. Only specify multiple grant records with
 *   different languages for a node if the site has those languages configured.
 *
 * A "deny all" grant may be used to deny all access to a particular node or
 * node translation:
 * @code
 * $grants[] = array(
 *   'realm' => 'all',
 *   'gid' => 0,
 *   'grant_view' => 0,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 *   'langcode' => 'ca',
 * );
 * @endcode
 * Note that another module node access module could override this by granting
 * access to one or more nodes, since grants are additive. To enforce that
 * access is denied in a particular case, use hook_node_access_records_alter().
 * Also note that a deny all is not written to the database; denies are
 * implicit.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that has just been saved.
 *
 * @return
 *   An array of grants as defined above.
 *
 * @see node_access_write_grants()
 * @see hook_node_access_records_alter()
 * @ingroup node_access
 */
function hook_node_access_records(\Drupal\node\NodeInterface $node) {
  // We only care about the node if it has been marked private. If not, it is
  // treated just like any other node and we completely ignore it.
  if ($node->private->value) {
    $grants = array();
    // Only published Catalan translations of private nodes should be viewable
    // to all users. If we fail to check $node->isPublished(), all users would be able
    // to view an unpublished node.
    if ($node->isPublished()) {
      $grants[] = array(
        'realm' => 'example',
        'gid' => 1,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'langcode' => 'ca'
      );
    }
    // For the example_author array, the GID is equivalent to a UID, which
    // means there are many groups of just 1 user.
    // Note that an author can always view his or her nodes, even if they
    // have status unpublished.
    $grants[] = array(
      'realm' => 'example_author',
      'gid' => $node->getOwnerId(),
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
      'langcode' => 'ca'
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
 * Upon viewing, editing or deleting a node, hook_node_grants() builds a
 * permissions array that is compared against the stored access records. The
 * user must have one or more matching permissions in order to complete the
 * requested operation.
 *
 * A module may deny all access to a node by setting $grants to an empty array.
 *
 * @param $grants
 *   The $grants array returned by hook_node_access_records().
 * @param \Drupal\node\NodeInterface $node
 *   The node for which the grants were acquired.
 *
 * The preferred use of this hook is in a module that bridges multiple node
 * access modules with a configurable behavior, as shown in the example with the
 * 'is_preview' field.
 *
 * @see hook_node_access_records()
 * @see hook_node_grants()
 * @see hook_node_grants_alter()
 * @ingroup node_access
 */
function hook_node_access_records_alter(&$grants, Drupal\node\NodeInterface $node) {
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
 * The resulting grants are then checked against the records stored in the
 * {node_access} table to determine if the operation may be completed.
 *
 * A module may deny all access to a user by setting $grants to an empty array.
 *
 * Developers may use this hook to either add additional grants to a user or to
 * remove existing grants. These rules are typically based on either the
 * permissions assigned to a user role, or specific attributes of a user
 * account.
 *
 * @param array $grants
 *   The $grants array returned by hook_node_grants().
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account requesting access to content.
 * @param string $op
 *   The operation being performed, 'view', 'update' or 'delete'.
 *
 * @see hook_node_grants()
 * @see hook_node_access_records()
 * @see hook_node_access_records_alter()
 * @ingroup node_access
 */
function hook_node_grants_alter(&$grants, \Drupal\Core\Session\AccountInterface $account, $op) {
  // Our sample module never allows certain roles to edit or delete
  // content. Since some other node access modules might allow this
  // permission, we expressly remove it by returning an empty $grants
  // array for roles specified in our variable setting.

  // Get our list of banned roles.
  $restricted = \Drupal::config('example.settings')->get('restricted_roles');

  if ($op != 'view' && !empty($restricted)) {
    // Now check the roles for this account against the restrictions.
    foreach ($account->getRoles() as $rid) {
      if (in_array($rid, $restricted)) {
        $grants = array();
      }
    }
  }
}

/**
 * Act before node deletion.
 *
 * This hook is invoked from entity_delete_multiple() before
 * hook_entity_predelete() is called and field values are deleted, and before
 * the node is removed from the node table in the database.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that is about to be deleted.
 *
 * @see hook_node_predelete()
 * @see entity_delete_multiple()
 * @ingroup node_api_hooks
 */
function hook_node_predelete(\Drupal\node\NodeInterface $node) {
  db_delete('mytable')
    ->condition('nid', $node->id())
    ->execute();
}

/**
 * Respond to node deletion.
 *
 * This hook is invoked from entity_delete_multiple() after field values are
 * deleted and after the node has been removed from the database.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that has been deleted.
 *
 * @see hook_node_predelete()
 * @see entity_delete_multiple()
 * @ingroup node_api_hooks
 */
function hook_node_delete(\Drupal\node\NodeInterface $node) {
  drupal_set_message(t('Node: @title has been deleted', array('@title' => $node->label())));
}

/**
 * Respond to deletion of a node revision.
 *
 * This hook is invoked from node_revision_delete() after the revision has been
 * removed from the node_revision table, and before field values are deleted.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node revision (node object) that is being deleted.
 *
 * @ingroup node_api_hooks
 */
function hook_node_revision_delete(\Drupal\node\NodeInterface $node) {
  db_delete('mytable')
    ->condition('vid', $node->getRevisionId())
    ->execute();
}

/**
 * Respond to creation of a new node.
 *
 * This hook is invoked from $node->save() after the database query that will
 * insert the node into the node table is scheduled for execution, and after
 * field values are saved.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the save operation is entirely completed
 * and $node->save() goes out of scope. You should not rely on data in the
 * database at this time as it is not updated yet. You should also note that any
 * write/update database queries executed from this hook are also not committed
 * immediately. Check $node->save() and db_transaction() for more info.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that is being created.
 *
 * @ingroup node_api_hooks
 */
function hook_node_insert(\Drupal\node\NodeInterface $node) {
  db_insert('mytable')
    ->fields(array(
      'nid' => $node->id(),
      'extra' => $node->extra,
    ))
    ->execute();
}

/**
 * Act on a newly created node.
 *
 * This hook runs after a new node object has just been instantiated. It can be
 * used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node object.
 *
 * @ingroup node_api_hooks
 */
function hook_node_create(\Drupal\node\NodeInterface $node) {
  if (!isset($node->foo)) {
    $node->foo = 'some_initial_value';
  }
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
 * via classes Drupal\node\NodeStorage and
 * Drupal\Core\Entity\ContentEntityDatabaseStorage. After the node information
 * and field values are read from the database or the entity cache,
 * hook_entity_load() is invoked on all implementing modules, and finally
 * hook_node_load() is invoked on all implementing modules.
 *
 * @param $nodes
 *   An array of the nodes being loaded, keyed by nid.
 *
 * For a detailed usage example, see nodeapi_example.module.
 *
 * @ingroup node_api_hooks
 */
function hook_node_load($nodes) {
  // Decide whether any of $types are relevant to our purposes.
  $types_we_want_to_process = \Drupal::config('my_types')->get('types');
  $nids = array();
  foreach ($nodes as $node) {
    if (in_array($node->bundle(), $types_we_want_to_process)) {
      $nids = $node->id();
    }
  }
  if ($nids) {
    // Gather our extra data for each of these nodes.
    $result = db_query('SELECT nid, foo FROM {mytable} WHERE nid IN(:nids)', array(':nids' => $nids));
    // Add our extra data to the node objects.
    foreach ($result as $record) {
      $nodes[$record->nid]->foo = $record->foo;
    }
  }
}

/**
 * Controls access to a node.
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
 * @param \Drupal\node\NodeInterface|string $node
 *   Either a node entity or the machine name of the content type on which to
 *   perform the access check.
 * @param string $op
 *   The operation to be performed. Possible values:
 *   - "create"
 *   - "delete"
 *   - "update"
 *   - "view"
 * @param object $account
 *   The user object to perform the access check operation on.
 * @param object $langcode
 *   The language code to perform the access check operation on.
 *
 * @return string
 *   - NODE_ACCESS_ALLOW: if the operation is to be allowed.
 *   - NODE_ACCESS_DENY: if the operation is to be denied.
 *   - NODE_ACCESS_IGNORE: to not affect this operation at all.
 *
 * @ingroup node_access
 */
function hook_node_access(\Drupal\node\NodeInterface $node, $op, $account, $langcode) {
  $type = is_string($node) ? $node : $node->getType();

  $configured_types = node_permissions_get_configured_types();
  if (isset($configured_types[$type])) {
    if ($op == 'create' && user_access('create ' . $type . ' content', $account)) {
      return NODE_ACCESS_ALLOW;
    }

    if ($op == 'update') {
      if (user_access('edit any ' . $type . ' content', $account) || (user_access('edit own ' . $type . ' content', $account) && ($account->id() == $node->getOwnerId()))) {
        return NODE_ACCESS_ALLOW;
      }
    }

    if ($op == 'delete') {
      if (user_access('delete any ' . $type . ' content', $account) || (user_access('delete own ' . $type . ' content', $account) && ($account->id() == $node->getOwnerId()))) {
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
 * This hook is invoked from NodeForm::prepareEntity().
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that is about to be shown on the form.
 * @param $operation
 *   The current operation.
 * @param array $form_state
 *   An associative array containing the current state of the form.
 *
 * @ingroup node_api_hooks
 */
function hook_node_prepare_form(\Drupal\node\NodeInterface $node, $operation, array &$form_state) {
  if (!isset($node->my_rating)) {
    $node->my_rating = \Drupal::config("my_rating_{$node->bundle()}")->get('enabled');
  }
}

/**
 * Act on a node being displayed as a search result.
 *
 * This hook is invoked from the node search plugin during search execution,
 * after loading and rendering the node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being displayed in a search result.
 * @param $langcode
 *   Language code of result being displayed.
 *
 * @return array
 *   Extra information to be displayed with search result. This information
 *   should be presented as an associative array. It will be concatenated with
 *   the post information (last updated, author) in the default search result
 *   theming.
 *
 * @see template_preprocess_search_result()
 * @see search-result.html.twig
 *
 * @ingroup node_api_hooks
 */
function hook_node_search_result(\Drupal\node\NodeInterface $node, $langcode) {
  $rating = db_query('SELECT SUM(points) FROM {my_rating} WHERE nid = :nid', array('nid' => $node->id()))->fetchField();
  return array('rating' => format_plural($rating, '1 point', '@count points'));
}

/**
 * Act on a node being inserted or updated.
 *
 * This hook is invoked from $node->save() before the node is saved to the
 * database.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that is being inserted or updated.
 *
 * @ingroup node_api_hooks
 */
function hook_node_presave(\Drupal\node\NodeInterface $node) {
  if ($node->id() && $node->moderate) {
    // Reset votes when node is updated:
    $node->score = 0;
    $node->users = '';
    $node->votes = 0;
  }
}

/**
 * Respond to updates to a node.
 *
 * This hook is invoked from $node->save() after the database query that will
 * update node in the node table is scheduled for execution, and after field
 * values are saved.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the save operation is entirely completed
 * and $node->save() goes out of scope. You should not rely on data in the
 * database at this time as it is not updated yet. You should also note that any
 * write/update database queries executed from this hook are also not committed
 * immediately. Check $node->save() and db_transaction() for more info.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node that is being updated.
 *
 * @ingroup node_api_hooks
 */
function hook_node_update(\Drupal\node\NodeInterface $node) {
  db_update('mytable')
    ->fields(array('extra' => $node->extra))
    ->condition('nid', $node->id())
    ->execute();
}

/**
 * Act on a node being indexed for searching.
 *
 * This hook is invoked during search indexing, after loading, and after the
 * result of rendering is added as $node->rendered to the node object.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being indexed.
 * @param $langcode
 *   Language code of the variant of the node being indexed.
 *
 * @return string
 *   Additional node information to be indexed.
 *
 * @ingroup node_api_hooks
 */
function hook_node_update_index(\Drupal\node\NodeInterface $node, $langcode) {
  $text = '';
  $ratings = db_query('SELECT title, description FROM {my_ratings} WHERE nid = :nid', array(':nid' => $node->id()));
  foreach ($ratings as $rating) {
    $text .= '<h2>' . String::checkPlain($rating->title) . '</h2>' . Xss::filter($rating->description);
  }
  return $text;
}

/**
 * Perform node validation before a node is created or updated.
 *
 * This hook is invoked from NodeForm::validate(), after a user has
 * finished editing the node and is previewing or submitting it. It is invoked
 * at the end of all the standard validation steps.
 *
 * To indicate a validation error, use form_set_error().
 *
 * Note: Changes made to the $node object within your hook implementation will
 * have no effect.  The preferred method to change a node's content is to use
 * hook_node_presave() instead. If it is really necessary to change the node at
 * the validate stage, you can use form_set_value().
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being validated.
 * @param $form
 *   The form being used to edit the node.
 * @param $form_state
 *   The form state array.
 *
 * @ingroup node_api_hooks
 */
function hook_node_validate(\Drupal\node\NodeInterface $node, $form, &$form_state) {
  if (isset($node->end) && isset($node->start)) {
    if ($node->start > $node->end) {
      form_set_error('time', $form_state, t('An event may not end before it starts.'));
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
 * properties.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node entity being updated in response to a form submission.
 * @param $form
 *   The form being used to edit the node.
 * @param $form_state
 *   The form state array.
 *
 * @ingroup node_api_hooks
 */
function hook_node_submit(\Drupal\node\NodeInterface $node, $form, &$form_state) {
  // Decompose the selected menu parent option into 'menu_name' and 'plid', if
  // the form used the default parent selection widget.
  if (!empty($form_state['values']['menu']['parent'])) {
    list($node->menu['menu_name'], $node->menu['plid']) = explode(':', $form_state['values']['menu']['parent']);
  }
}

/**
 * Act on a node that is being assembled before rendering.
 *
 * The module may add elements to a node's renderable array array prior to
 * rendering.
 *
 * When $view_mode is 'rss', modules can also add extra RSS elements and
 * namespaces to $node->rss_elements and $node->rss_namespaces respectively for
 * the RSS item generated for this node.
 * For details on how this is used, see node_feed().
 *
 * @param array &$build
 *   A renderable array representing the node content.
 * @param \Drupal\node\NodeInterface $node
 *   The node that is being assembled for rendering.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the node
 *   components.
 * @param string $view_mode
 *   The $view_mode parameter from node_view().
 * @param string $langcode
 *   The language code used for rendering.
 *
 * @see forum_node_view()
 * @see hook_entity_view()
 *
 * @ingroup node_api_hooks
 */
function hook_node_view(array &$build, \Drupal\node\NodeInterface $node, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // node type in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = array(
      '#markup' => mymodule_addition($node),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
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
 * callback.  Alternatively, it could also implement hook_preprocess_HOOK() for
 * node.html.twig. See drupal_render() and _theme() documentation respectively
 * for details.
 *
 * @param &$build
 *   A renderable array representing the node content.
 * @param \Drupal\node\NodeInterface $node
 *   The node being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the node
 *   components.
 *
 * @see node_view()
 * @see hook_entity_view_alter()
 *
 * @ingroup node_api_hooks
 */
function hook_node_view_alter(array &$build, \Drupal\node\NodeInterface $node, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the node.
  $build['#post_render'][] = 'my_module_node_post_render';
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
  if (\Drupal::config('vote.settings')->get('node_enabled')) {
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
        'arguments' => array(\Drupal::config('vote.settings')->get('score_max')),
      ),
    );
  }
}

/**
 * Respond to node type creation.
 *
 * @param \Drupal\node\NodeTypeInterface $type
 *   The node type entity that was created.
 */
function hook_node_type_insert(\Drupal\node\NodeTypeInterface $type) {
  drupal_set_message(t('You have just created a content type with a machine name %type.', array('%type' => $type->id())));
}

/**
 * Respond to node type updates.
 *
 * @param \Drupal\node\NodeTypeInterface $type
 *   The node type entity that was updated.
 */
function hook_node_type_update(\Drupal\node\NodeTypeInterface $type) {
  if ($type->original->id() != $type->id()) {
    drupal_set_message(t('You have just changed the machine name of a content type from %old_type to %type.', array('%old_type' => $type->original->id(), '%type' => $type->id())));
  }
}

/**
 * Respond to node type deletion.
 *
 * @param \Drupal\node\NodeTypeInterface $type
 *   The node type entity that was deleted.
 */
function hook_node_type_delete(\Drupal\node\NodeTypeInterface $type) {
  drupal_set_message(t('You have just deleted a content type with the machine name %type.', array('%type' => $type->id())));
}

/**
 * Alter the links of a node.
 *
 * @param array &$links
 *   A renderable array representing the node links.
 * @param \Drupal\node\NodeInterface $entity
 *   The node being rendered.
 * @param array &$context
 *   Various aspects of the context in which the node links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the comment is being viewed
 *   - 'langcode': the language in which the comment is being viewed
 *
 * @see \Drupal\node\NodeViewBuilder::renderLinks()
 * @see \Drupal\node\NodeViewBuilder::buildLinks()
 */
function hook_node_links_alter(array &$links, NodeInterface $entity, array &$context) {
  $links['mymodule'] = array(
    '#theme' => 'links__node__mymodule',
    '#attributes' => array('class' => array('links', 'inline')),
    '#links' => array(
      'node-report' => array(
        'title' => t('Report'),
        'href' => "node/{$entity->id()}/report",
        'html' => TRUE,
        'query' => array('token' => \Drupal::getContainer()->get('csrf_token')->get("node/{$entity->id()}/report")),
      ),
    ),
  );
}

/**
 * @} End of "addtogroup hooks".
 */
