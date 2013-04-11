<?php
/**
* @file
* Contains \Drupal\devel_node_access\Plugin\block\block\DnaUser.
*/

namespace Drupal\devel_node_access\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\devel_node_access\DnaBlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\block\Plugin\Core\Entity\Block;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Provides the "Devel Node Access by User" block.
 *
 *
 * @Plugin(
 *   id = "devel_dna_user_block",
 *   admin_label = @Translation("Devel Node Access by User"),
 *   module = "devel_node_access"
 * )
 */
class DnaUser extends DnaBlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
  public function blockAccess() {
    return user_access(DNA_ACCESS_VIEW);
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $form_state = array();
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['callback'] = array($this, 'buildForm');
    $form = drupal_build_form('devel_node_access_by_user_form', $form_state);
    return $form;
  }

  /**
   * Builds the content of the block.
   *
   * @return array|null
   *   A renderable array representing the content of the block.
   */
  public function buildForm() {
    global $user;

    $output = array();

    // Show which users can access this node.
    $menu_item = menu_get_item();
    $map = $menu_item['original_map'];
    if ($map[0] != 'node' || !isset($map[1]) || !is_numeric($map[1]) || isset($map[2])) {
      // Ignore anything but node/%.
      return NULL;
    }

    if (isset($menu_item['map'][1]) && is_object($node = $menu_item['map'][1])) {
      // We have the node.
    }
    elseif (is_numeric($menu_item['original_map'][1])) {
      $node = node_load($menu_item['original_map'][1]);
    }
    if (isset($node)) {
      $nid = $node->nid;
      $langcode = $node->langcode;
      $language = language_load($langcode);
      $node_type = node_type_load($node->type);
      $headers = array(t('username'), '<span title="' . t("Create '@langname'-language nodes of the '@Node_type' type.", array('@langname' => $language->name, '@Node_type' => $node_type->name)) . '">' . t('create') . '</span>', t('view'), t('update'), t('delete'));
      $rows = array();
      // Determine whether to use Ajax or pre-populate the tables.
      if ($ajax = config('devel_node_access.settings')->get('user_ajax')) {
        drupal_add_js(drupal_get_path('module', 'devel_node_access') . '/devel_node_access.js');
      }
      // Find all users. The following operations are very inefficient, so we
      // limit the number of users returned.  It would be better to make a
      // pager query, or at least make the number of users configurable.  If
      // anyone is up for that please submit a patch.
      $query = db_select('users', 'u')
        ->fields('u', array('uid'))
        ->orderBy('u.access', 'DESC')
        ->range(0, 9);
      $uids = $query->execute()->fetchCol();
      array_unshift($uids, 0);
      $accounts = user_load_multiple($uids);
      foreach ($accounts as $account) {
        $username = theme('username', array('account' => $account));
        if ($account->uid == $user->uid) {
          $username = '<strong>' . $username . '</strong>';
        }
        $rows[] = array(
          $username,
          array(
            'id' => 'create-' . $nid . '-' . $account->uid,
            'class' => 'dna-permission',
            'data' => $ajax ? NULL : theme('dna_permission', array('permission' => self::explain_access('create', $node, $account, $langcode))),
          ),
          array(
            'id' => 'view-' . $nid . '-' . $account->uid,
            'class' => 'dna-permission',
            'data' => $ajax ? NULL : theme('dna_permission', array('permission' => self::explain_access('view', $node, $account, $langcode))),
          ),
          array(
            'id' => 'update-' . $nid . '-' . $account->uid,
            'class' => 'dna-permission',
            'data' => $ajax ? NULL : theme('dna_permission', array('permission' => self::explain_access('update', $node, $account, $langcode))),
          ),
          array(
            'id' => 'delete-' . $nid . '-' . $account->uid,
            'class' => 'dna-permission',
            'data' => $ajax ? NULL : theme('dna_permission', array('permission' => self::explain_access('delete', $node, $account, $langcode))),
          ),
        );
      }
      if (count($rows)) {
        $output['title'] = array(
          '#prefix' => '<h2>',
          '#markup' => t('Access permissions by user for the %langname language', array('%langname' => $language->name)),
          '#postfix' => '</h2>',
        );
        $output[] = array(
          '#theme'      => 'table',
          '#header'     => $headers,
          '#rows'       => $rows,
          '#attributes' => array('style' => 'text-align: left'),
        );
        $output[] = array(
          '#theme'        => 'form_element',
          '#description'  => t('(This table lists the most-recently active users. Hover your mouse over each result for more details.)'),
        );
      }
    }
    return $output;
  }

}
