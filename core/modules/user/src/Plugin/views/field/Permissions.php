<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Permissions.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of permissions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_permissions")
 */
class Permissions extends PrerenderList {

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->roleStorage = $entity_manager->getStorage('user_role');
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'), $container->get('entity.manager'));
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

    $permission_names = \Drupal::moduleHandler()->invokeAll('permission');

    $rids = array();
    foreach ($values as $result) {
      $user_rids = $this->getEntity($result)->getRoles();
      $uid = $this->getValue($result);

      foreach ($user_rids as $rid) {
        $rids[$rid][] = $uid;
      }
    }

    if ($rids) {
      $roles = $this->roleStorage->loadMultiple(array_keys($rids));
      foreach ($rids as $rid => $role_uids) {
        foreach ($roles[$rid]->getPermissions() as $permission) {
          foreach ($role_uids as $uid) {
            $this->items[$uid][$permission]['permission'] = $permission_names[$permission]['title'];
          }
        }
      }

      foreach ($uids as $uid) {
        if (isset($this->items[$uid])) {
          ksort($this->items[$uid]);
        }
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
