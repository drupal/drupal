<?php

namespace Drupal\user\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\PermissionHandlerInterface;
use Drupal\views\Attribute\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides permission-based access control.
 *
 * @ingroup views_access_plugins
 */
#[ViewsAccess(
  id: 'perm',
  title: new TranslatableMarkup('Permission'),
  help: new TranslatableMarkup('Access will be granted to users with the specified permission string.'),
)]
class Permission extends AccessPluginBase implements CacheableDependencyInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * The service properties that should raise a deprecation error.
   */
  private array $deprecatedProperties = ['moduleHandler' => 'module_handler'];

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Module extension list.
   */
  protected ModuleExtensionList $moduleExtensionList;

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
   * @param \Drupal\Core\Extension\ModuleExtensionList|\Drupal\Core\Extension\ModuleHandlerInterface $module_extension_list
   *   The module extension list.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler, ModuleExtensionList|ModuleHandlerInterface $module_extension_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    if ($module_extension_list instanceof ModuleHandlerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $module_extension_list argument as ModuleHandlerInterface is deprecated in drupal:10.3.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
      $module_extension_list = \Drupal::service('extension.list.module');
    }
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission($this->options['perm']);
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
    $options['perm'] = ['default' => 'access content'];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Get list of permissions
    $perms = [];
    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $display_name = $this->moduleExtensionList->getName($provider);
      $perms[$display_name][$perm] = strip_tags($perm_item['title']);
    }

    $form['perm'] = [
      '#type' => 'select',
      '#options' => $perms,
      '#title' => $this->t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => $this->t('Only users with the selected permission flag will be able to access this display.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
