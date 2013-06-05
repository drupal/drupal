<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Permissions.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;

/**
 * Field handler to provide a list of permissions.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user_permissions")
 */
class Permissions extends PrerenderList {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['uid'] = array('table' => 'users', 'field' => 'uid');
  }

  public function query() {
    $this->addAdditionalFields();
    $this->field_alias = $this->aliases['uid'];
  }

  function pre_render(&$values) {
    $uids = array();
    $this->items = array();

    foreach ($values as $result) {
      $uids[] = $this->getValue($result);
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
  protected function documentSelfTokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = t('The name of the role.');
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = t('The role ID of the role.');
  }

  protected function addSelfTokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
  }
  */

}
