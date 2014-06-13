<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Roles.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of roles.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_roles")
 */
class Roles extends PrerenderList {

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
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
      $roles = user_roles();
      $result = $this->database->query('SELECT u.uid, u.rid FROM {users_roles} u WHERE u.uid IN (:uids) AND u.rid IN (:rids)', array(':uids' => $uids, ':rids' => array_keys($roles)));
      foreach ($result as $role) {
        $this->items[$role->uid][$role->rid]['role'] = String::checkPlain($roles[$role->rid]->label());
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

  protected function documentSelfTokens(&$tokens) {
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
