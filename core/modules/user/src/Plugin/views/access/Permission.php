<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\access\Permission.
 */

namespace Drupal\user\Plugin\views\access;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "perm",
 *   title = @Translation("Permission"),
 *   help = @Translation("Access will be granted to users with the specified permission string.")
 * )
 */
class Permission extends AccessPluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission($this->options['perm']) || $account->hasPermission('access all views');
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_permission', $this->options['perm']);
  }

  public function summaryTitle() {
    $permissions = $this->permissionHandler->getPermissions();
    if (isset($permissions[$this->options['perm']])) {
      return $permissions[$this->options['perm']]['title'];
    }

    return $this->t($this->options['perm']);
  }


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['perm'] = array('default' => 'access content');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $module_info = system_get_info('module');

    // Get list of permissions
    $perms = [];
    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $display_name = $module_info[$provider]['name'];
      $perms[$display_name][$perm] = String::checkPlain(strip_tags($perm_item['title']));
    }

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $perms,
      '#title' => $this->t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => $this->t('Only users with the selected permission flag will be able to access this display. Note that users with "access all views" can see any view, regardless of other permissions.'),
    );
  }

}
