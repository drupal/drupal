<?php
/**
* @file
* Contains \Drupal\devel_node_access\DnaBlockBase.
*/

namespace Drupal\devel_node_access;

use Drupal\block\BlockBase;

/**
 * Provides the base class for the Devel Node Access blocks.
 */
abstract class DnaBlockBase extends BlockBase {

  /**
   * Helper function that mimics node.module's node_access() function.
   *
   * Unfortunately, this needs to be updated manually whenever node.module
   * changes!
   *
   * @param $op
   * @param $node
   * @param null $account
   * @param null $langcode
   *
   * @return array
   *   An array suitable for theming with theme_dna_permission().
   */
  public static function explain_access($op, $node, $account = NULL, $langcode = NULL) {
    global $user;

    if (!$node) {
      return array(
        FALSE,
        '???',
        t('No node passed to node_access(); this should never happen!'),
      );
    }
    if (!in_array($op, array('view', 'update', 'delete', 'create'), TRUE)) {
      return array(
        FALSE,
        t('!NO: invalid $op', array('!NO' => t('NO'))),
        t("'@op' is an invalid operation!", array('@op' => $op)),
      );
    }

    if ($op == 'create' && is_object($node)) {
      $node = $node->type;
    }

    if (!empty($account)) {
      // To try to get the most authentic result we impersonate the given user!
      // This may reveal bugs in other modules, leading to contradictory
      // results.
      $saved_user = $user;
      drupal_save_session(FALSE);
      $user = $account;
      $result = DnaBlockBase::explain_access($op, $node, NULL, $langcode);
      $user = $saved_user;
      drupal_save_session(TRUE);
      $second_opinion = node_access($op, $node, $account);
      if ($second_opinion != $result[0]) {
        $result[1] .= '<span class="' . ($second_opinion ? 'ok' : 'error') . '" title="Core seems to disagree on this item. This is a bug in either DNA or Core and should be fixed! Try to look at this node as this user and check whether there is still disagreement.">*</span>';
      }
      return $result;
    }

    if (empty($langcode)) {
      $langcode = (is_object($node) && isset($node->nid)) ? $node->langcode : '';
    }

    $variables = array(
      '!NO'                 => t('NO'),
      '!YES'                => t('YES'),
      '!bypass_node_access' => t('bypass node access'),
      '!access_content'     => t('access content'),
    );

    if (user_access('bypass node access')) {
      return array(
        TRUE,
        t('!YES: bypass node access', $variables),
        t("!YES: This user has the '!bypass_node_access' permission and may do everything with nodes.", $variables),
      );
    }

    if (!user_access('access content')) {
      return array(
        FALSE,
        t('!NO: access content', $variables),
        t("!NO: This user does not have the '!access_content' permission and is denied doing anything with content.", $variables),
      );
    }

    foreach (module_implements('node_access') as $module) {
      $function = $module . '_node_access';
      if (function_exists($function)) {
        $result = $function($node, $op, $user, $langcode);
        if ($module == 'node') {
          $module = 'node (permissions)';
        }
        if (isset($result)) {
          if ($result === NODE_ACCESS_DENY) {
            $denied_by[] = $module;
          }
          elseif ($result === NODE_ACCESS_ALLOW) {
            $allowed_by[] = $module;
          }
          $access[] = $result;
        }
      }
    }
    $variables += array(
      '@deniers'  => (empty($denied_by) ? NULL : implode(', ', $denied_by)),
      '@allowers' => (empty($allowed_by) ? NULL : implode(', ', $allowed_by)),
    );
    if (!empty($denied_by)) {
      $variables += array(
        '%module' => $denied_by[0] . (count($denied_by) > 1 ? '+' : ''),
      );
      return array(
        FALSE,
        t('!NO: by %module', $variables),
        empty($allowed_by)
          ? t("!NO: hook_node_access() of the following module(s) denies this: @deniers.", $variables)
          : t("!NO: hook_node_access() of the following module(s) denies this: @deniers &ndash; even though the following module(s) would allow it: @allowers.", $variables),
      );
    }
    if (!empty($allowed_by)) {
      $variables += array(
        '%module' => $allowed_by[0] . (count($allowed_by) > 1 ? '+' : ''),
        '!view_own_unpublished_content' => t('view own unpublished content'),
      );
      return array(
        TRUE,
        t('!YES: by %module', $variables),
        t("!YES: hook_node_access() of the following module(s) allows this: @allowers.", $variables),
      );
    }

    if ($op == 'view' && !$node->get('status', $langcode) && user_access('view own unpublished content') && $user->uid == $node->get('uid', $langcode) && $user->uid != 0) {
      return array(
        TRUE,
        t('!YES: view own unpublished content', $variables),
        t("!YES: The node is unpublished, but the user has the '!view_own_unpublished_content' permission.", $variables),
      );
    }

    if ($op != 'create' && $node->nid) {
      if (node_access($op, $node, $user, $langcode)) {  // delegate this part
        $variables['@node_access_table'] = '{node_access}';
        return array(
          TRUE,
          t('!YES: @node_access_table', $variables),
          t('!YES: Node access allows this based on one or more records in the @node_access_table table (see the other DNA block!).', $variables),
        );
      }
    }

    return array(
      FALSE,
      t('!NO: no reason', $variables),
      t("!NO: None of the checks resulted in allowing this, so it's denied.", $variables)
        . ($op == 'create' ? ' ' . t('This is most likely due to a withheld permission.') : ''),
    );
  }

  /**
   * Collects the IDs of the visible nodes on the current page.
   *
   * @param null $nid
   *   A node ID to save.
   *
   * @return array
   *   The array of saved node IDs.
   */
  public static function visible_nodes($nid = NULL) {
    static $nids = array();
    if (isset($nid)) {
      $nids[$nid] = $nid;
    }
    elseif (empty($nids)) {
      $menu_item = menu_get_item();
      $map = $menu_item['original_map'];
      if ($map[0] == 'node' && isset($map[1]) && is_numeric($map[1]) && !isset($map[2])) {
        // Show DNA information on node/NID even if access is denied
        // (IF the user has the 'view devel_node_access information' perm)!
        $nids[$map[1]] = $map[1];
      }
    }
    return $nids;
  }

}