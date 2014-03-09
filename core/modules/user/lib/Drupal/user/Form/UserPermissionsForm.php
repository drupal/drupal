<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserPermissionsForm.
 */

namespace Drupal\user\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\user\RoleStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form.
 */
class UserPermissionsForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageControllerInterface
   */
  protected $roleStorage;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\RoleStorageControllerInterface $role_storage
   *   The role storage.
   */
  public function __construct(ModuleHandlerInterface $module_handler, RoleStorageControllerInterface $role_storage) {
    $this->moduleHandler = $module_handler;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity.manager')->getStorageController('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_admin_permissions';
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $role_names = array();
    $role_permissions = array();
    foreach ($this->getRoles() as $role_name => $role) {
      // Retrieve role names for columns.
      $role_names[$role_name] = String::checkPlain($role->label());
      // Fetch permissions for the roles.
      $role_permissions[$role_name] = $role->getPermissions();
    }

    // Store $role_names for use when saving the data.
    $form['role_names'] = array(
      '#type' => 'value',
      '#value' => $role_names,
    );
    // Render role/permission overview:
    $options = array();
    $module_info = system_rebuild_module_data();
    $hide_descriptions = system_admin_compact_mode();

    // Get a list of all the modules implementing a hook_permission() and sort by
    // display name.
    $modules = array();
    foreach ($this->moduleHandler->getImplementations('permission') as $module) {
      $modules[$module] = $module_info[$module]->info['name'];
    }
    asort($modules);

    $form['system_compact_link'] = array(
      '#theme' => 'system_compact_link',
    );

    $form['permissions'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Permission')),
      '#id' => 'permissions',
      '#sticky' => TRUE,
    );
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = array(
        'data' => $name,
        'class' => array('checkbox'),
      );
    }

    foreach ($modules as $module => $display_name) {
      if ($permissions = $this->moduleHandler->invoke($module, 'permission')) {
        // Module name.
        $form['permissions'][$module] = array(array(
          '#wrapper_attributes' => array(
            'colspan' => count($role_names) + 1,
            'class' => array('module'),
            'id' => 'module-' . $module,
          ),
          '#markup' => $module_info[$module]->info['name'],
        ));
        foreach ($permissions as $perm => $perm_item) {
          // Fill in default values for the permission.
          $perm_item += array(
            'description' => '',
            'restrict access' => FALSE,
            'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
          );
          $options[$perm] = $perm_item['title'];
          $user_permission_description = array(
            '#theme' => 'user_permission_description',
            '#permission_item' => $perm_item,
            '#hide' => $hide_descriptions,
          );
          $form['permissions'][$perm]['description'] = array(
            '#wrapper_attributes' => array(
              'class' => array('permission'),
            ),
            '#type' => 'item',
            '#markup' => $perm_item['title'],
            '#description' => drupal_render($user_permission_description),
          );
          $options[$perm] = '';
          foreach ($role_names as $rid => $name) {
            $form['permissions'][$perm][$rid] = array(
              '#title' => $name . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => array(
                'class' => array('checkbox'),
              ),
              '#type' => 'checkbox',
              '#default_value' => in_array($perm,$role_permissions[$rid]) ? 1 : 0,
              '#attributes' => array('class' => array('rid-' . $rid)),
              '#parents' => array($rid, $perm),
            );
          }
        }
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Save permissions'));

    $form['#attached']['library'][] = 'user/drupal.user.permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['role_names'] as $role_name => $name) {
      user_role_change_permissions($role_name, $form_state['values'][$role_name]);
    }

    drupal_set_message($this->t('The changes have been saved.'));

    // Clear the cached pages and blocks.
    Cache::invalidateTags(array('content' => TRUE));
  }

}
