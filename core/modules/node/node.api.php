<?php

/**
 * @file
 * Hooks specific to the Node module.
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

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
 * Grant IDs can be arbitrarily defined by a node access module using a list of
 * integer IDs associated with users.
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
 * $record = [
 *   'nid' => 0,
 *   'gid' => 888,
 *   'realm' => 'example_realm',
 *   'grant_view' => 1,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 * ];
 * \Drupal::database()->insert('node_access')->fields($record)->execute();
 * @endcode
 * And then in its hook_node_grants() implementation, it would need to return:
 * @code
 * if ($op == 'view') {
 *   $grants['example_realm'] = [888];
 * }
 * @endcode
 * If you decide to do this, be aware that
 * \Drupal\node\NodeAccessRebuild::rebuild() will erase any node ID 0 entry when
 * it is called, so you will need to make sure to restore your {node_access}
 * record after \Drupal\node\NodeAccessRebuild::rebuild() is called.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account object whose grants are requested.
 * @param string $operation
 *   The node operation to be performed, such as 'view', 'update', or 'delete'.
 *
 * @return array
 *   An array whose keys are "realms" of grants, and whose values are arrays of
 *   the grant IDs within this realm that this user is being granted.
 *
 * @see \Drupal\node\NodeGrantDatabaseStorage::checkAllGrants()
 * @see \Drupal\node\NodeAccessRebuild::rebuild()
 * @ingroup node_access
 */
function hook_node_grants(AccountInterface $account, $operation): array {
  $grants = [];
  if ($account->hasPermission('access private content')) {
    $grants['example'] = [1];
  }
  if ($account->id()) {
    $grants['example_author'] = [$account->id()];
  }
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
 *   set to $node->isPublished(). Failure to do so may expose unpublished
 *   content to some users.
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
 * $grants[] = [
 *   'realm' => 'all',
 *   'gid' => 0,
 *   'grant_view' => 0,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 *   'langcode' => 'ca',
 * ];
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
 * @return array
 *   An array of grants as defined above.
 *
 * @see hook_node_access_records_alter()
 * @ingroup node_access
 */
function hook_node_access_records(NodeInterface $node): array {
  $grants = [];
  // We only care about the node if it has been marked private. If not, it is
  // treated just like any other node and we completely ignore it.
  if ($node->private->value) {
    // Only published Catalan translations of private nodes should be viewable
    // to all users. If we fail to check $node->isPublished(), all users would
    // be able to view an unpublished node.
    if ($node->isPublished()) {
      $grants[] = [
        'realm' => 'example',
        'gid' => 1,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'langcode' => 'ca',
      ];
    }
    // For the example_author array, the GID is equivalent to a UID, which
    // means there are many groups of just 1 user.
    // Note that an author can always view nodes they own, even if they have
    // status unpublished.
    if ($node->getOwnerId()) {
      $grants[] = [
        'realm' => 'example_author',
        'gid' => $node->getOwnerId(),
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
        'langcode' => 'ca',
      ];
    }
  }
  return $grants;
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
 * The preferred use of this hook is in a module that bridges multiple node
 * access modules with a configurable behavior, as shown in the example with the
 * 'is_preview' field.
 *
 * @param array $grants
 *   The $grants array returned by hook_node_access_records().
 * @param \Drupal\node\NodeInterface $node
 *   The node for which the grants were acquired.
 *
 * @see hook_node_access_records()
 * @see hook_node_grants()
 * @see hook_node_grants_alter()
 * @ingroup node_access
 */
function hook_node_access_records_alter(&$grants, NodeInterface $node) {
  // Our module allows editors to mark specific articles with the 'is_preview'
  // field. If the node being saved has a TRUE value for that field, then only
  // our grants are retained, and other grants are removed. Doing so ensures
  // that our rules are enforced no matter what priority other grants are given.
  if ($node->is_preview) {
    // Our module grants are set in $grants['example'].
    $temp = $grants['example'];
    // Now remove all module grants but our own.
    $grants = ['example' => $temp];
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
 * @param string $operation
 *   The operation being performed, 'view', 'update' or 'delete'.
 *
 * @see hook_node_grants()
 * @see hook_node_access_records()
 * @see hook_node_access_records_alter()
 * @ingroup node_access
 */
function hook_node_grants_alter(&$grants, AccountInterface $account, $operation) {
  // Our sample module never allows certain roles to edit or delete
  // content. Since some other node access modules might allow this
  // permission, we expressly remove it by returning an empty $grants
  // array for roles specified in our variable setting.

  // Get our list of banned roles.
  $restricted = \Drupal::config('example.settings')->get('restricted_roles');

  if ($operation != 'view' && !empty($restricted)) {
    // Now check the roles for this account against the restrictions.
    foreach ($account->getRoles() as $rid) {
      if (in_array($rid, $restricted)) {
        $grants = [];
      }
    }
  }
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
 *   - 'view_mode': the view mode in which the node is being viewed.
 *   - 'langcode': the language in which the node is being viewed.
 *
 * @see \Drupal\node\NodeViewBuilder::renderLinks()
 * @see \Drupal\node\NodeViewBuilder::buildLinks()
 * @see entity_crud
 */
function hook_node_links_alter(array &$links, NodeInterface $entity, array &$context) {
  $links['my_module'] = [
    '#theme' => 'links__node__my_module',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'node-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('node_test.report', ['node' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("node/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
