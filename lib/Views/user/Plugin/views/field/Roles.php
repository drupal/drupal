<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\field\Roles.
 */

namespace Views\user\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_roles",
 *   module = "user"
 * )
 */
class Roles extends PrerenderList {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

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
      $query = db_select('role', 'r');
      $query->join('users_roles', 'u', 'u.rid = r.rid');
      $query->addField('r', 'name');
      $query->fields('u', array('uid', 'rid'));
      $query->condition('u.uid', $uids);
      $query->orderBy('r.name');
      $result = $query->execute();
      foreach ($result as $role) {
        $this->items[$role->uid][$role->rid]['role'] = check_plain($role->name);
        $this->items[$role->uid][$role->rid]['rid'] = $role->rid;
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

  function add_self_tokens(&$tokens, $item) {
    if (!empty($item['role'])) {
      $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
      $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
    }
  }

}
