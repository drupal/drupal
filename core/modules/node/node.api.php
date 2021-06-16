<?php

/**
 * @file
 * Hooks specific to the Node module.
 */

use Drupal\node\NodeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

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
 * \Drupal::database()->insert('node_access')->fields($record)->execute();
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
 * For a detailed example, see node_access_example.module.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account object whose grants are requested.
 * @param string $op
 *   The node operation to be performed, such as 'view', 'update', or 'delete'.
 *
 * @return array
 *   An array whose keys are "realms" of grants, and whose values are arrays of
 *   the grant IDs within this realm that this user is being granted.
 *
 * @see node_access_view_all_nodes()
 * @see node_access_rebuild()
 * @ingroup node_access
 */
function hook_node_grants(\Drupal\Core\Session\AccountInterface $account, $op) {
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
 * @see hook_node_access_records_alter()
 * @ingroup node_access
 */
function hook_node_access_records(\Drupal\node\NodeInterface $node) {
  // We only care about the node if it has been marked private. If not, it is
  // treated just like any other node and we completely ignore it.
  if ($node->private->value) {
    $grants = [];
    // Only published Catalan translations of private nodes should be viewable
    // to all users. If we fail to check $node->isPublished(), all users would be able
    // to view an unpublished node.
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
function hook_node_access_records_alter(&$grants, \Drupal\node\NodeInterface $node) {
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
        $grants = [];
      }
    }
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
 * @ingroup entity_crud
 */
function hook_node_search_result(\Drupal\node\NodeInterface $node) {
  $rating = \Drupal::database()->query('SELECT SUM([points]) FROM {my_rating} WHERE [nid] = :nid', ['nid' => $node->id()])->fetchField();
  return ['rating' => \Drupal::translation()->formatPlural($rating, '1 point', '@count points')];
}

/**
 * Act on a node being indexed for searching.
 *
 * This hook is invoked during search indexing, after loading, and after the
 * result of rendering is added as $node->rendered to the node object.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node being indexed.
 *
 * @return string
 *   Additional node information to be indexed.
 *
 * @ingroup entity_crud
 */
function hook_node_update_index(\Drupal\node\NodeInterface $node) {
  $text = '';
  $ratings = \Drupal::database()->query('SELECT [title], [description] FROM {my_ratings} WHERE [nid] = :nid', [':nid' => $node->id()]);
  foreach ($ratings as $rating) {
    $text .= '<h2>' . Html::escape($rating->title) . '</h2>' . Xss::filter($rating->description);
  }
  return $text;
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
 * @return array
 *   An associative array of ranking data. The keys should be strings,
 *   corresponding to the internal name of the ranking mechanism, such as
 *   'recent', or 'comments'. The values should be arrays themselves, with the
 *   following keys available:
 *   - title: (required) The human readable name of the ranking mechanism.
 *   - join: (optional) An array with information to join any additional
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
 * @ingroup entity_crud
 */
function hook_ranking() {
  // If voting is disabled, we can avoid returning the array, no hard feelings.
  if (\Drupal::config('vote.settings')->get('node_enabled')) {
    return [
      'vote_average' => [
        'title' => t('Average vote'),
        // Note that we use i.sid, the search index's search item id, rather than
        // n.nid.
        'join' => [
          'type' => 'LEFT',
          'table' => 'vote_node_data',
          'alias' => 'vote_node_data',
          'on' => 'vote_node_data.nid = i.sid',
        ],
        // The highest possible score should be 1, and the lowest possible score,
        // always 0, should be 0.
        'score' => 'vote_node_data.average / CAST(%f AS DECIMAL)',
        // Pass in the highest possible voting score as a decimal argument.
        'arguments' => [\Drupal::config('vote.settings')->get('score_max')],
      ],
    ];
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
 *   - 'view_mode': the view mode in which the node is being viewed
 *   - 'langcode': the language in which the node is being viewed
 *
 * @see \Drupal\node\NodeViewBuilder::renderLinks()
 * @see \Drupal\node\NodeViewBuilder::buildLinks()
 * @see entity_crud
 */
function hook_node_links_alter(array &$links, NodeInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__node__mymodule',
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
