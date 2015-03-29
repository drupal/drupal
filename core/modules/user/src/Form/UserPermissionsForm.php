<?php

/**
 * @file
 * Contains \Drupal\user\Form\UserPermissionsForm.
 */

namespace Drupal\user\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form.
 */
class UserPermissionsForm extends FormBase {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage) {
    $this->permissionHandler = $permission_handler;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('entity.manager')->getStorage('user_role')
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $role_names = array();
    $role_permissions = array();
    $admin_roles = array();
    foreach ($this->getRoles() as $role_name => $role) {
      // Retrieve role names for columns.
      $role_names[$role_name] = SafeMarkup::checkPlain($role->label());
      // Fetch permissions for the roles.
      $role_permissions[$role_name] = $role->getPermissions();
      $admin_roles[$role_name] = $role->isAdmin();
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

    $form['system_compact_link'] = array(
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    );

    $form['permissions'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Permission')),
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions']],
      '#sticky' => TRUE,
    );
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = array(
        'data' => $name,
        'class' => array('checkbox'),
      );
    }

    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = array();
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    foreach ($permissions_by_provider as $provider => $permissions) {
      // Module name.
      $form['permissions'][$provider] = array(array(
        '#wrapper_attributes' => array(
          'colspan' => count($role_names) + 1,
          'class' => array('module'),
          'id' => 'module-' . $provider,
        ),
        '#markup' => $module_info[$provider]->info['name'],
      ));
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += array(
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        );
        $options[$perm] = $perm_item['title'];
        $form['permissions'][$perm]['description'] = array(
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => array(
            'title' => $perm_item['title'],
          ),
        );
        // Show the permission description.
        if (!$hide_descriptions) {
          $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        }
        $options[$perm] = '';
        foreach ($role_names as $rid => $name) {
          $form['permissions'][$perm][$rid] = array(
            '#title' => $name . ': ' . $perm_item['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => array(
              'class' => array('checkbox'),
            ),
            '#type' => 'checkbox',
            '#default_value' => in_array($perm, $role_permissions[$rid]) ? 1 : 0,
            '#attributes' => array('class' => array('rid-' . $rid)),
            '#parents' => array($rid, $perm),
          );
          // Show a column of disabled but checked checkboxes.
          if ($admin_roles[$rid]) {
            $form['permissions'][$perm][$rid]['#disabled'] = TRUE;
            $form['permissions'][$perm][$rid]['#default_value'] = TRUE;
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
  function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      user_role_change_permissions($role_name, (array) $form_state->getValue($role_name));
    }

    drupal_set_message($this->t('The changes have been saved.'));
  }

}
