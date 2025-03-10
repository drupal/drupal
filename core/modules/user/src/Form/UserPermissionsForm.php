<?php

namespace Drupal\user\Form;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form.
 *
 * @internal
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList|null $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler, protected ?ModuleExtensionList $moduleExtensionList = NULL) {
    $this->permissionHandler = $permission_handler;
    $this->roleStorage = $role_storage;
    $this->moduleHandler = $module_handler;
    if ($this->moduleExtensionList === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $moduleExtensionList argument is deprecated in drupal:10.3.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
      $this->moduleExtensionList = \Drupal::service('extension.list.module');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('module_handler'),
      $container->get('extension.list.module'),
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
    return $this->roleStorage->loadMultipleOverrideFree();
  }

  /**
   * Group permissions by the modules that provide them.
   *
   * @return string[][]
   *   A nested array. The outer keys are modules that provide permissions. The
   *   inner arrays are permission names keyed by their machine names.
   */
  protected function permissionsByProvider(): array {
    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    // Move the access content permission to the Node module if it is installed.
    // @todo Add an alter so that this section can be moved to the Node module.
    if ($this->moduleHandler->moduleExists('node')) {
      // Insert 'access content' before the 'view own unpublished content' key
      // in order to maintain the UI even though the permission is provided by
      // the system module.
      $keys = array_keys($permissions_by_provider['node']);
      $offset = (int) array_search('view own unpublished content', $keys);
      $permissions_by_provider['node'] = array_merge(
        array_slice($permissions_by_provider['node'], 0, $offset),
        ['access content' => $permissions_by_provider['system']['access content']],
        array_slice($permissions_by_provider['node'], $offset)
      );
      unset($permissions_by_provider['system']['access content']);
    }

    return $permissions_by_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $role_names = [];
    $role_permissions = [];
    $admin_roles = [];
    foreach ($this->getRoles() as $role_name => $role) {
      // Retrieve role names for columns.
      $role_names[$role_name] = $role->label();
      // Fetch permissions for the roles.
      $role_permissions[$role_name] = $role->getPermissions();
      $admin_roles[$role_name] = $role->isAdmin();
    }

    // Store $role_names for use when saving the data.
    $form['role_names'] = [
      '#type' => 'value',
      '#value' => $role_names,
    ];
    // Render role/permission overview:
    $hide_descriptions = system_admin_compact_mode();

    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter permissions'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by permission name'),
      '#description' => $this->t('Enter permission name'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#permissions',
        'autocomplete' => 'off',
      ],
    ];

    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = [
        'data' => $name,
        'class' => ['checkbox'],
      ];
    }

    foreach ($this->permissionsByProvider() as $provider => $permissions) {
      // Module name.
      $form['permissions'][$provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($role_names) + 1,
            'class' => ['module'],
            'id' => 'module-' . $provider,
          ],
          '#markup' => $this->moduleExtensionList->getName($provider),
        ],
      ];
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];
        $form['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title table-filter-text-source">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
          ],
        ];
        // Show the permission description.
        if (!$hide_descriptions) {
          $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        }
        foreach ($role_names as $rid => $name) {
          $form['permissions'][$perm][$rid] = [
            '#title' => $name . ': ' . $perm_item['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
            '#type' => 'checkbox',
            '#default_value' => in_array($perm, $role_permissions[$rid]) ? 1 : 0,
            '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid]],
            '#parents' => [$rid, $perm],
          ];
          // Show a column of disabled but checked checkboxes.
          if ($admin_roles[$rid]) {
            $form['permissions'][$perm][$rid]['#disabled'] = TRUE;
            $form['permissions'][$perm][$rid]['#default_value'] = TRUE;
          }
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'user/drupal.user.permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      user_role_change_permissions($role_name, (array) $form_state->getValue($role_name));
    }

    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

}
