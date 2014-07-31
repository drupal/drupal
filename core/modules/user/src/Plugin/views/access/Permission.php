<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\access\Permission.
 */

namespace Drupal\user\Plugin\views\access;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
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
    $permissions = \Drupal::moduleHandler()->invokeAll('permission');
    if (isset($permissions[$this->options['perm']])) {
      return $permissions[$this->options['perm']]['title'];
    }

    return t($this->options['perm']);
  }


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['perm'] = array('default' => 'access content');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $perms = array();
    $module_info = system_get_info('module');

    // Get list of permissions
    foreach (\Drupal::moduleHandler()->getImplementations('permission') as $module) {
      $permissions = \Drupal::moduleHandler()->invoke($module, 'permission');
      foreach ($permissions as $name => $perm) {
        $perms[$module_info[$module]['name']][$name] = strip_tags($perm['title']);
      }
    }

    ksort($perms);

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $perms,
      '#title' => t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => t('Only users with the selected permission flag will be able to access this display. Note that users with "access all views" can see any view, regardless of other permissions.'),
    );
  }

}
