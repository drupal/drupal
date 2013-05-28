<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Roles.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user_roles")
 */
class Roles extends PrerenderList {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['uid'] = array('table' => 'users', 'field' => 'uid');
  }

  public function query() {
    $this->add_additional_fields();
    $this->field_alias = $this->aliases['uid'];
  }

  function pre_render(&$values) {
    $uids = array();
    $this->items = array();

    foreach ($values as $result) {
      $uids[] = $this->get_value($result);
    }

    if ($uids) {
      $roles = user_roles();
      $query = db_select('users_roles', 'u');
      $query->fields('u', array('uid', 'rid'));
      $query->condition('u.rid', array_keys($roles));
      $query->condition('u.uid', $uids);

      $result = $query->execute();
      foreach ($result as $role) {
        $this->items[$role->uid][$role->rid]['role'] = check_plain($roles[$role->rid]->label());
        $this->items[$role->uid][$role->rid]['rid'] = $role->rid;
      }
      // Sort the roles for each user by role weight.
      $ordered_roles = array_flip(array_keys($roles));
      foreach ($this->items as &$user_roles) {
        // Create an array of rids that the user has in the role weight order.
        $sorted_keys  = array_intersect_key($ordered_roles, $user_roles);
        // Merge with the unsorted array of role information which has the
        // effect of sorting it.
        $user_roles = array_merge($sorted_keys, $user_roles);
      }
    }
  }

  function render_item($count, $item) {
    return $item['role'];
  }

  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = t('The name of the role.');
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = t('The role machine-name of the role.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    if (!empty($item['role'])) {
      $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
      $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
    }
  }

}
