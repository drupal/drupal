<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\field\Permissions.
 */

namespace Views\user\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;

/**
 * Field handler to provide a list of permissions.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_permissions",
 *   module = "user"
 * )
 */
class Permissions extends PrerenderList {

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
      // Get a list of all the modules implementing a hook_permission() and sort by
      // display name.
      $module_info = system_get_info('module');
      $modules = array();
      foreach (module_implements('permission') as $module) {
        $modules[$module] = $module_info[$module]['name'];
      }
      asort($modules);

      $permissions = module_invoke_all('permission');

      $query = db_select('role_permission', 'rp');
      $query->join('users_roles', 'u', 'u.rid = rp.rid');
      $query->fields('u', array('uid', 'rid'));
      $query->addField('rp', 'permission');
      $query->condition('u.uid', $uids);
      $query->condition('rp.module', array_keys($modules));
      $query->orderBy('rp.permission');
      $result = $query->execute();

      foreach ($result as $perm) {
        $this->items[$perm->uid][$perm->permission]['permission'] = $permissions[$perm->permission]['title'];
      }
    }
  }

  function render_item($count, $item) {
    return $item['permission'];
  }

  /*
  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = t('The name of the role.');
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = t('The role ID of the role.');
  }

  function add_self_tokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
  }
  */

}
