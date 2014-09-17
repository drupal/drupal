<?php

use Drupal\node\NodeInterface;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;

/**
 * @file
 * Hooks specific to the Node module.
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
  if ($account->hasPermission('access private content')) {
    $grants['example'] = array(1);
  }
  $grants['example_author'] = array($account->id());
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
 * your module does not want to explicitly allow or forbid access, return an
 * AccessResultInterface object with neither isAllowed() nor isForbidden()
 * equaling TRUE. Blindly returning an object with isForbidden() equaling TRUE
 * will break other node access modules.
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
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user object to perform the access check operation on.
 * @param object $langcode
 *   The language code to perform the access check operation on.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *    The access result.
 *
 * @ingroup node_access
 */
function hook_node_access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account, $langcode) {
  $type = $node->bundle();

  switch ($op) {
    case 'create':
      return AccessResult::allowedIfHasPermission($account, 'create ' . $type . ' content');

    case 'update':
      if ($account->hasPermission('edit any ' . $type . ' content', $account)) {
        return AccessResult::allowed()->cachePerRole();
      }
      else {
        return AccessResult::allowedIf($account->hasPermission('edit own ' . $type . ' content', $account) && ($account->id() == $node->getOwnerId()))->cachePerRole()->cachePerUser()->cacheUntilEntityChanges($node);
      }

    case 'delete':
      if ($account->hasPermission('delete any ' . $type . ' content', $account)) {
        return AccessResult::allowed()->cachePerRole();
      }
      else {
        return AccessResult::allowedIf($account->hasPermission('delete own ' . $type . ' content', $account) && ($account->id() == $node->getOwnerId()))->cachePerRole()->cachePerUser()->cacheUntilEntityChanges($node);
      }

    default:
      // No opinion.
      return AccessResult::create();
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
 * @ingroup entity_crud
 */
function hook_node_search_result(\Drupal\node\NodeInterface $node, $langcode) {
  $rating = db_query('SELECT SUM(points) FROM {my_rating} WHERE nid = :nid', array('nid' => $node->id()))->fetchField();
  return array('rating' => format_plural($rating, '1 point', '@count points'));
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
 * @ingroup entity_crud
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
 * To indicate a validation error, use $form_state->setErrorByName().
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
 *   The current state of the form.
 *
 * @ingroup entity_crud
 */
function hook_node_validate(\Drupal\node\NodeInterface $node, $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  if (isset($node->end) && isset($node->start)) {
    if ($node->start > $node->end) {
      $form_state->setErrorByName('time', t('An event may not end before it starts.'));
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
 * $form_state->getValues(). This hook is intended for adjusting non-field-related
 * properties.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node entity being updated in response to a form submission.
 * @param $form
 *   The form being used to edit the node.
 * @param $form_state
 *   The current state of the form.
 *
 * @ingroup entity_crud
 */
function hook_node_submit(\Drupal\node\NodeInterface $node, $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // Decompose the selected menu parent option into 'menu_name' and 'parent', if
  // the form used the default parent selection widget.
  $parent = $form_state->getValue(array('menu', 'parent'));
  if (!empty($parent)) {
    list($node->menu['menu_name'], $node->menu['parent']) = explode(':', $parent);
  }
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
 * @ingroup entity_crud
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
 * @see entity_crud
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
