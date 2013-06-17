<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Permissions.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of permissions.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user_permissions")
 */
class Permissions extends PrerenderList {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

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

  public function preRender(&$values) {
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

      $result = $this->database->query('SELECT u.uid, u.rid, rp.permission FROM {role_permission} rp INNER JOIN {users_roles} u ON u.rid = rp.rid WHERE u.uid IN (:uids) AND rp.module IN (:modules) ORDER BY rp.permission',
        array(':uids' => $uids, ':modules' => array_keys($modules)));

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
