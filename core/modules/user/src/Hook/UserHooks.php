<?php

namespace Drupal\user\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\Role;
use Drupal\filter\FilterFormatInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Action;
use Drupal\Component\Assertion\Inspector;
use Drupal\user\RoleInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user.
 */
class UserHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.user':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The User module allows users to register, log in, and log out. It also allows users with proper permissions to manage user roles and permissions. For more information, see the <a href=":user_docs">online documentation for the User module</a>.', [':user_docs' => 'https://www.drupal.org/documentation/modules/user']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Creating and managing users') . '</dt>';
        $output .= '<dd>' . t('Through the <a href=":people">People administration page</a> you can add and cancel user accounts and assign users to roles. By editing one particular user you can change their username, email address, password, and information in other fields.', [':people' => Url::fromRoute('entity.user.collection')->toString()]) . '</dd>';
        $output .= '<dt>' . t('Configuring user roles') . '</dt>';
        $output .= '<dd>' . t('<em>Roles</em> are used to group and classify users; each user can be assigned one or more roles. Typically there are two pre-defined roles: <em>Anonymous user</em> (users that are not logged in), and <em>Authenticated user</em> (users that are registered and logged in). Depending on how your site was set up, an <em>Administrator</em> role may also be available: users with this role will automatically be assigned any new permissions whenever a module is installed. You can create additional roles on the <a href=":roles">Roles administration page</a>.', [
          ':roles' => Url::fromRoute('entity.user_role.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Setting permissions') . '</dt>';
        $output .= '<dd>' . t('After creating roles, you can set permissions for each role on the <a href=":permissions_user">Permissions page</a>. Granting a permission allows users who have been assigned a particular role to perform an action on the site, such as viewing content, editing or creating  a particular type of content, administering settings for a particular module, or using a particular function of the site (such as search).', [
          ':permissions_user' => Url::fromRoute('user.admin_permissions')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Other permissions pages') . '</dt>';
        $output .= '<dd>' . t('The main Permissions page can be overwhelming, so each module that defines permissions has its own page for setting them. There are links to these pages on the <a href=":modules">Extend page</a>. When editing a content type, vocabulary, etc., there is also a Manage permissions tab for permissions related to that configuration.', [':modules' => Url::fromRoute('system.modules_list')->toString()]) . '</dd>';
        $output .= '<dt>' . t('Managing account settings') . '</dt>';
        $output .= '<dd>' . t('The <a href=":accounts">Account settings page</a> allows you to manage settings for the displayed name of the Anonymous user role, personal contact forms, user registration settings, and account cancellation settings. On this page you can also manage settings for account personalization, and adapt the text for the email messages that users receive when they register or request a password recovery. You may also set which role is automatically assigned new permissions whenever a module is installed (the Administrator role).', [':accounts' => Url::fromRoute('entity.user.admin_form')->toString()]) . '</dd>';
        $output .= '<dt>' . t('Managing user account fields') . '</dt>';
        $output .= '<dd>' . t('Because User accounts are an entity type, you can extend them by adding fields through the Manage fields tab on the <a href=":accounts">Account settings page</a>. By adding fields for e.g., a picture, a biography, or address, you can a create a custom profile for the users of the website. For background information on entities and fields, see the <a href=":field_help">Field module help page</a>.', [
          ':field_help' => \Drupal::moduleHandler()->moduleExists('field') ? Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString() : '#',
          ':accounts' => Url::fromRoute('entity.user.admin_form')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'user.admin_create':
        return '<p>' . t("This web page allows administrators to register new users. Users' email addresses and usernames must be unique.") . '</p>';

      case 'user.admin_permissions':
        return '<p>' . t('Permissions let you control what users can do and see on your site. You can define a specific set of permissions for each role. (See the <a href=":role">Roles</a> page to create a role.) Any permissions granted to the Authenticated user role will be given to any user who is logged in to your site. On the <a href=":settings">Role settings</a> page, you can make any role into an Administrator role for the site, meaning that role will be granted all permissions. You should be careful to ensure that only trusted users are given this access and level of control of your site.', [
          ':role' => Url::fromRoute('entity.user_role.collection')->toString(),
          ':settings' => Url::fromRoute('user.role.settings')->toString(),
        ]) . '</p>';

      case 'entity.user_role.collection':
        return '<p>' . t('A role defines a group of users that have certain privileges. These privileges are defined on the <a href=":permissions">Permissions page</a>. Here, you can define the names and the display sort order of the roles on your site. It is recommended to order roles from least permissive (for example, Anonymous user) to most permissive (for example, Administrator user). Users who are not logged in have the Anonymous user role. Users who are logged in have the Authenticated user role, plus any other roles granted to their user account.', [
          ':permissions' => Url::fromRoute('user.admin_permissions')->toString(),
        ]) . '</p>';

      case 'entity.user.field_ui_fields':
        return '<p>' . t('This form lets administrators add and edit fields for storing user data.') . '</p>';

      case 'entity.entity_form_display.user.default':
        return '<p>' . t('This form lets administrators configure how form fields should be displayed when editing a user profile.') . '</p>';

      case 'entity.entity_view_display.user.default':
        return '<p>' . t('This form lets administrators configure how fields should be displayed when rendering a user profile page.') . '</p>';
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'user' => [
        'render element' => 'elements',
      ],
      'username' => [
        'variables' => [
          'account' => NULL,
          'attributes' => [],
          'link_options' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_js_settings_alter().
   */
  #[Hook('js_settings_alter')]
  public function jsSettingsAlter(&$settings, AttachedAssetsInterface $assets): void {
    // Provide the user ID in drupalSettings to allow JavaScript code to customize
    // the experience for the end user, rather than the server side, which would
    // break the render cache.
    // Similarly, provide a permissions hash, so that permission-dependent data
    // can be reliably cached on the client side.
    $user = \Drupal::currentUser();
    $settings['user']['uid'] = $user->id();
    $settings['user']['permissionsHash'] = \Drupal::service('user_permissions_hash_generator')->generate($user);
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $fields['user']['user']['form']['account'] = [
      'label' => t('User name and password'),
      'description' => t('User module account form elements.'),
      'weight' => -10,
    ];
    $fields['user']['user']['form']['language'] = [
      'label' => t('Language settings'),
      'description' => t('User module form element.'),
      'weight' => 0,
    ];
    if (\Drupal::config('system.date')->get('timezone.user.configurable')) {
      $fields['user']['user']['form']['timezone'] = [
        'label' => t('Timezone'),
        'description' => t('System module form element.'),
        'weight' => 6,
      ];
    }
    $fields['user']['user']['display']['member_for'] = [
      'label' => t('Member for'),
      'description' => t("User module 'member for' view element."),
      'weight' => 5,
    ];
    return $fields;
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for user entities.
   *
   * @todo https://www.drupal.org/project/drupal/issues/3112704 Move to
   *   \Drupal\user\Entity\User::preSave().
   */
  #[Hook('user_presave')]
  public function userPresave(UserInterface $account) {
    $config = \Drupal::config('system.date');
    if ($config->get('timezone.user.configurable') && !$account->getTimeZone() && !$config->get('timezone.user.default')) {
      $account->timezone = $config->get('timezone.default');
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for user entities.
   */
  #[Hook('user_view')]
  public function userView(array &$build, UserInterface $account, EntityViewDisplayInterface $display) {
    if ($account->isAuthenticated() && $display->getComponent('member_for')) {
      $build['member_for'] = [
        '#type' => 'item',
        '#markup' => '<h4 class="label">' . t('Member for') . '</h4> ' . \Drupal::service('date.formatter')->formatTimeDiffSince($account->getCreatedTime()),
      ];
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter() for user entities.
   *
   * This function adds a default alt tag to the user_picture field to maintain
   * accessibility.
   */
  #[Hook('user_view_alter')]
  public function userViewAlter(array &$build, UserInterface $account, EntityViewDisplayInterface $display): void {
    if (!empty($build['user_picture']) && user_picture_enabled()) {
      foreach (Element::children($build['user_picture']) as $key) {
        if (!isset($build['user_picture'][$key]['#item']) || !$build['user_picture'][$key]['#item'] instanceof ImageItem) {
          // User picture field is provided by standard profile install. If the
          // display is configured to use a different formatter, the #item render
          // key may not exist, or may not be an image field.
          continue;
        }
        /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
        $item = $build['user_picture'][$key]['#item'];
        if (!$item->get('alt')->getValue()) {
          $item->get('alt')->setValue(\Drupal::translation()->translate('Profile picture for user @username', ['@username' => $account->getAccountName()]));
        }
      }
    }
  }

  /**
   * Implements hook_template_preprocess_default_variables_alter().
   *
   * @see user_user_login()
   * @see user_user_logout()
   */
  #[Hook('template_preprocess_default_variables_alter')]
  public function templatePreprocessDefaultVariablesAlter(&$variables): void {
    $user = \Drupal::currentUser();
    $variables['user'] = clone $user;
    // Remove password and session IDs, since themes should not need nor see them.
    unset($variables['user']->pass, $variables['user']->sid, $variables['user']->ssid);
    $variables['is_admin'] = $user->hasPermission('access administration pages');
    $variables['logged_in'] = $user->isAuthenticated();
  }

  /**
   * Implements hook_user_login().
   */
  #[Hook('user_login')]
  public function userLogin(UserInterface $account) {
    // Reset static cache of default variables in template_preprocess() to reflect
    // the new user.
    drupal_static_reset('template_preprocess');
    // If the user has a NULL time zone, notify them to set a time zone.
    $config = \Drupal::config('system.date');
    if (!$account->getTimezone() && $config->get('timezone.user.configurable') && $config->get('timezone.user.warn')) {
      \Drupal::messenger()->addStatus(t('Configure your <a href=":user-edit">account time zone setting</a>.', [
        ':user-edit' => $account->toUrl('edit-form', [
          'query' => \Drupal::destination()->getAsArray(),
          'fragment' => 'edit-timezone',
        ])->toString(),
      ]));
    }
  }

  /**
   * Implements hook_user_logout().
   */
  #[Hook('user_logout')]
  public function userLogout(AccountInterface $account) {
    // Reset static cache of default variables in template_preprocess() to reflect
    // the new user.
    drupal_static_reset('template_preprocess');
  }

  /**
   * Implements hook_mail().
   */
  #[Hook('mail')]
  public function mail($key, &$message, $params) {
    $token_service = \Drupal::token();
    $language_manager = \Drupal::languageManager();
    $langcode = $message['langcode'];
    $variables = ['user' => $params['account']];
    $language = $language_manager->getLanguage($langcode);
    $original_language = $language_manager->getConfigOverrideLanguage();
    $language_manager->setConfigOverrideLanguage($language);
    $mail_config = \Drupal::config('user.mail');
    $token_options = ['langcode' => $langcode, 'callback' => 'user_mail_tokens', 'clear' => TRUE];
    $message['subject'] .= PlainTextOutput::renderFromHtml($token_service->replace($mail_config->get($key . '.subject'), $variables, $token_options));
    $message['body'][] = $token_service->replace($mail_config->get($key . '.body'), $variables, $token_options);
    $language_manager->setConfigOverrideLanguage($original_language);
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for user_role entities.
   */
  #[Hook('user_role_insert')]
  public function userRoleInsert(RoleInterface $role) {
    // Ignore the authenticated and anonymous roles or the role is being synced.
    if (in_array($role->id(), [
      RoleInterface::AUTHENTICATED_ID,
      RoleInterface::ANONYMOUS_ID,
    ]) || $role->isSyncing()) {
      return;
    }
    assert(Inspector::assertStringable($role->label()), 'Role label is expected to be a string.');
    $add_id = 'user_add_role_action.' . $role->id();
    if (!Action::load($add_id)) {
      $action = Action::create([
        'id' => $add_id,
        'type' => 'user',
        'label' => t('Add the @label role to the selected user(s)', [
          '@label' => $role->label(),
        ]),
        'configuration' => [
          'rid' => $role->id(),
        ],
        'plugin' => 'user_add_role_action',
      ]);
      $action->trustData()->save();
    }
    $remove_id = 'user_remove_role_action.' . $role->id();
    if (!Action::load($remove_id)) {
      $action = Action::create([
        'id' => $remove_id,
        'type' => 'user',
        'label' => t('Remove the @label role from the selected user(s)', [
          '@label' => $role->label(),
        ]),
        'configuration' => [
          'rid' => $role->id(),
        ],
        'plugin' => 'user_remove_role_action',
      ]);
      $action->trustData()->save();
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for user_role entities.
   */
  #[Hook('user_role_delete')]
  public function userRoleDelete(RoleInterface $role) {
    // Delete role references for all users.
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user_storage->deleteRoleReferences([$role->id()]);
    // Ignore the authenticated and anonymous roles or the role is being synced.
    if (in_array($role->id(), [
      RoleInterface::AUTHENTICATED_ID,
      RoleInterface::ANONYMOUS_ID,
    ]) || $role->isSyncing()) {
      return;
    }
    $actions = Action::loadMultiple(['user_add_role_action.' . $role->id(), 'user_remove_role_action.' . $role->id()]);
    foreach ($actions as $action) {
      $action->delete();
    }
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$types): void {
    if (isset($types['password_confirm'])) {
      $types['password_confirm']['#process'][] = 'user_form_process_password_confirm';
    }
  }

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled($modules) {
    // Remove any potentially orphan module data stored for users.
    \Drupal::service('user.data')->delete($modules);
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar() {
    $user = \Drupal::currentUser();
    $items['user'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'link',
        '#title' => $user->getDisplayName(),
        '#url' => Url::fromRoute('user.page'),
        '#attributes' => [
          'title' => t('My account'),
          'class' => [
            'toolbar-icon',
            'toolbar-icon-user',
          ],
        ],
        '#cache' => [
                  // Vary cache for anonymous and authenticated users.
          'contexts' => [
            'user.roles:anonymous',
          ],
        ],
      ],
      'tray' => [
        '#heading' => t('User account actions'),
      ],
      '#weight' => 100,
      '#attached' => [
        'library' => [
          'user/drupal.user.icons',
        ],
      ],
    ];
    if ($user->isAnonymous()) {
      $links = [
        'login' => [
          'title' => t('Log in'),
          'url' => Url::fromRoute('user.page'),
        ],
      ];
      $items['user']['tray']['user_links'] = [
        '#theme' => 'links__toolbar_user',
        '#links' => $links,
        '#attributes' => [
          'class' => [
            'toolbar-menu',
          ],
        ],
      ];
    }
    else {
      $items['user']['tab']['#title'] = [
        '#lazy_builder' => [
          'user.toolbar_link_builder:renderDisplayName',
                [],
        ],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
                // Add a line of whitespace to the placeholder to ensure the icon is
                // positioned in the same place it will be when the lazy loaded content
                // appears.
          '#markup' => '&nbsp;',
        ],
      ];
      $items['user']['tray']['user_links'] = [
        '#lazy_builder' => [
          'user.toolbar_link_builder:renderToolbarLinks',
                [],
        ],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          '#markup' => '<a href="#" class="toolbar-tray-lazy-placeholder-link">&nbsp;</a>',
        ],
      ];
    }
    return $items;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for \Drupal\system\Form\RegionalForm.
   */
  #[Hook('form_system_regional_settings_alter')]
  public function formSystemRegionalSettingsAlter(&$form, FormStateInterface $form_state) : void {
    $config = \Drupal::config('system.date');
    $form['timezone']['configurable_timezones'] = [
      '#type' => 'checkbox',
      '#title' => t('Users may set their own time zone'),
      '#default_value' => $config->get('timezone.user.configurable'),
    ];
    $form['timezone']['configurable_timezones_wrapper'] = [
      '#type' => 'container',
      '#states' => [
              // Hide the user configured timezone settings when users are forced to use
              // the default setting.
        'invisible' => [
          'input[name="configurable_timezones"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['timezone']['configurable_timezones_wrapper']['empty_timezone_message'] = [
      '#type' => 'checkbox',
      '#title' => t('Remind users at login if their time zone is not set'),
      '#default_value' => $config->get('timezone.user.warn'),
      '#description' => t('Only applied if users may set their own time zone.'),
    ];
    $form['timezone']['configurable_timezones_wrapper']['user_default_timezone'] = [
      '#type' => 'radios',
      '#title' => t('Time zone for new users'),
      '#default_value' => $config->get('timezone.user.default'),
      '#options' => [
        UserInterface::TIMEZONE_DEFAULT => t('Default time zone'),
        UserInterface::TIMEZONE_EMPTY => t('Empty time zone'),
        UserInterface::TIMEZONE_SELECT => t('Users may set their own time zone at registration'),
      ],
      '#description' => t('Only applied if users may set their own time zone.'),
    ];
    $form['#submit'][] = 'user_form_system_regional_settings_submit';
  }

  /**
   * Implements hook_filter_format_disable().
   */
  #[Hook('filter_format_disable')]
  public function filterFormatDisable(FilterFormatInterface $filter_format) {
    // Remove the permission from any roles.
    $permission = $filter_format->getPermissionName();
    /** @var \Drupal\user\Entity\Role $role */
    foreach (Role::loadMultiple() as $role) {
      if ($role->hasPermission($permission)) {
        $role->revokePermission($permission)->save();
      }
    }
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) {
    // Add Manage permissions link if this entity type defines the permissions
    // link template.
    if (!$entity->hasLinkTemplate('entity-permissions-form')) {
      return [];
    }
    $bundle_entity_type = $entity->bundle();
    $route = "entity.{$bundle_entity_type}.entity_permissions_form";
    if (empty(\Drupal::service('router.route_provider')->getRoutesByNames([$route]))) {
      return [];
    }
    $url = Url::fromRoute($route, [$bundle_entity_type => $entity->id()]);
    if (!$url->access()) {
      return [];
    }
    return [
      'manage-permissions' => [
        'title' => t('Manage permissions'),
        'weight' => 50,
        'url' => $url,
      ],
    ];
  }

}
