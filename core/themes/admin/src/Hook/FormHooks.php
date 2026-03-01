<?php

declare(strict_types=1);

namespace Drupal\admin\Hook;

use Drupal\admin\Helper;
use Drupal\admin\Settings;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings as CoreSettings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Form\ViewsForm;
use Drupal\views_ui\Form\Ajax\ViewsFormInterface;

// cspell:ignore apng

/**
 * Provides form related hook implementations.
 */
class FormHooks {

  use AjaxHelperTrait;
  use StringTranslationTrait;

  /**
   * Constructs the form related hooks.
   */
  public function __construct(
    protected ClassResolverInterface $classResolver,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly StateInterface $state,
    protected readonly MessengerInterface $messenger,
    protected RouteMatchInterface $routeMatch,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $build_info = $form_state->getBuildInfo();
    $form_object = $form_state->getFormObject();

    // Make entity forms delete link use the action-link component.
    if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] === 'link' && !empty($build_info['callback_object']) && $build_info['callback_object'] instanceof EntityForm) {
      $form['actions']['delete'] = Helper::convertLinkToActionLink($form['actions']['delete'], 'trash', 'default', 'danger');
    }

    if (isset($form['actions']['delete_translation']['#type']) && $form['actions']['delete_translation']['#type'] === 'link' && !empty($build_info['callback_object']) && $build_info['callback_object'] instanceof EntityForm) {
      $form['actions']['delete_translation'] = Helper::convertLinkToActionLink($form['actions']['delete_translation'], 'trash', 'default', 'danger');
    }

    if (($form_object instanceof ViewsForm || $form_object instanceof ViewsFormInterface) && isset($form['override']['#prefix'])) {
      // Replace form--inline class so positioning of override form elements
      // don't have to depend on floats.
      $form['override']['#prefix'] = str_replace('form--inline', 'form--flex', $form['override']['#prefix']);
    }

    if ($form_object instanceof ViewsForm && str_starts_with($form_object->getBaseFormId(), 'views_form_media_library')) {
      if (isset($form['header'])) {
        $form['header']['#attributes']['class'][] = 'media-library-views-form__header';
        $form['header']['media_bulk_form']['#attributes']['class'][] = 'media-library-views-form__bulk_form';
      }
      $form['actions']['submit']['#attributes']['class'] = ['media-library-select'];
      $form['#attributes']['class'][] = 'media-library-views-form';
    }

    if ($form_object instanceof ViewsForm && !empty($form['header'])) {
      $view = $form_state->getBuildInfo()['args'][0];
      $view_title = $view->getTitle();

      // Determine if the Views form includes a bulk operations form. If it
      // does, move it to the bottom and remove the second bulk operations
      // submit.
      foreach (Element::children($form['header']) as $key) {
        if (str_contains($key, '_bulk_form')) {
          // Move the bulk actions form from the header to its own container.
          $form['bulk_actions_container'] = $form['header'][$key];
          // Remove the supplementary bulk operations submit button as it
          // appears in the same location the form was moved to.
          unset($form['header'][$key], $form['actions']);

          $form['bulk_actions_container']['#attributes']['data-drupal-views-bulk-actions'] = '';
          $form['bulk_actions_container']['#attributes']['class'][] = 'views-bulk-actions';
          $form['bulk_actions_container']['actions']['submit']['#button_type'] = 'primary';
          $form['bulk_actions_container']['actions']['submit']['#attributes']['class'][] = 'button--small';
          $label = $this->t('Perform actions on the selected items in the %view_title view', ['%view_title' => $view_title]);
          $label_id = $key . '_group_label';

          // Group the bulk actions select and submit elements, and add a label
          // that makes the purpose of these elements more clear to screen
          // readers.
          $form['bulk_actions_container']['#attributes']['role'] = 'group';
          $form['bulk_actions_container']['#attributes']['aria-labelledby'] = $label_id;
          $form['bulk_actions_container']['group_label'] = [
            '#type' => 'container',
            '#markup' => $label,
            '#attributes' => [
              'id' => $label_id,
              'class' => ['visually-hidden'],
            ],
            '#weight' => -1,
          ];

          // Add a status label for counting the number of items selected.
          $form['bulk_actions_container']['status'] = [
            '#type' => 'container',
            '#markup' => $this->t('No items selected'),
            '#weight' => -1,
            '#attributes' => [
              'class' => [
                'js-views-bulk-actions-status',
                'views-bulk-actions__item',
                'views-bulk-actions__item--status',
                'js-show',
              ],
              'data-drupal-views-bulk-actions-status' => '',
            ],
          ];

          // Loop through bulk actions items and add the needed CSS classes.
          $bulk_action_item_keys = Element::children($form['bulk_actions_container'], TRUE);
          $bulk_last_key = NULL;
          $bulk_child_before_actions_key = NULL;
          foreach ($bulk_action_item_keys as $bulk_action_item_key) {
            if (!empty($form['bulk_actions_container'][$bulk_action_item_key]['#type'])) {
              if ($form['bulk_actions_container'][$bulk_action_item_key]['#type'] === 'actions') {
                // We need the key of the element that precedes the actions
                // element.
                $bulk_child_before_actions_key = $bulk_last_key;
                $form['bulk_actions_container'][$bulk_action_item_key]['#attributes']['class'][] = 'views-bulk-actions__item';
              }

              if (!in_array($form['bulk_actions_container'][$bulk_action_item_key]['#type'], ['hidden', 'actions'])) {
                $form['bulk_actions_container'][$bulk_action_item_key]['#wrapper_attributes']['class'][] = 'views-bulk-actions__item';
                $bulk_last_key = $bulk_action_item_key;
              }
            }
          }

          if ($bulk_child_before_actions_key) {
            $form['bulk_actions_container'][$bulk_child_before_actions_key]['#wrapper_attributes']['class'][] = 'views-bulk-actions__item--preceding-actions';
          }
        }
      }
    }

    $this->stickyActionButtonsAndSidebar($form, $form_state, $form_id);

    // Bulk forms: update action & actions to small variants.
    if (isset($form['header']) && str_contains($form_id, 'views_form')) {
      $bulk_form = current(preg_grep('/_bulk_form/', array_keys($form['header'])));

      if (isset($form['header'][$bulk_form])) {
        $form['header'][$bulk_form]['action']['#attributes']['class'][] = 'form-element--type-select--small';
        $form['header'][$bulk_form]['actions']['submit']['#attributes']['class'][] = 'button--small';

        // Remove double entry of submit button.
        unset($form['actions']['submit']);
      }
    }

    // Delete forms: alter buttons.
    if (str_contains($form_id, 'delete_form')) {
      $form['actions']['submit']['#attributes']['class'][] = 'button--danger';
      $form['actions']['cancel']['#attributes']['class'][] = 'button--secondary';
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for \Drupal\media\MediaForm.
   */
  #[Hook('form_media_form_alter')]
  public function formMediaFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['#attached']['library'][] = 'admin/media-form';
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_media_library_add_form_alter')]
  public function formMediaLibraryAddFormAlter(array &$form): void {
    $form['#attributes']['class'][] = 'media-library-add-form';
    $form['#attached']['library'][] = 'admin/media_library.theme';

    // If there are unsaved media items, apply styling classes to various parts
    // of the form.
    if (isset($form['media'])) {
      $form['#attributes']['class'][] = 'media-library-add-form--with-input';

      // Put a wrapper around the informational message above the unsaved media
      // items.
      $form['description']['#template'] = '<p class="media-library-add-form__description">{{ text }}</p>';
    }
    else {
      $form['#attributes']['class'][] = 'media-library-add-form--without-input';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_media_library_add_form_oembed_alter')]
  public function formMediaLibraryAddFormOembedAlter(array &$form): void {
    $form['#attributes']['class'][] = 'media-library-add-form--oembed';

    // If no media items have been added yet, add a couple of styling classes
    // to the initial URL form.
    if (isset($form['container'])) {
      $form['container']['#attributes']['class'][] = 'media-library-add-form__input-wrapper';
      $form['container']['url']['#attributes']['class'][] = 'media-library-add-form-oembed-url';
      $form['container']['submit']['#attributes']['class'][] = 'media-library-add-form-oembed-submit';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_media_library_add_form_upload_alter')]
  public function formMediaLibraryAddFormUploadAlter(array &$form): void {
    $form['#attributes']['class'][] = 'media-library-add-form--upload';
    if (isset($form['container']['upload'])) {
      // Set this flag so we can prevent the details element from being added
      // in \Drupal\admin\Hook\ThemeHooks::managedFile.
      $form['container']['upload']['#do_not_wrap_in_details'] = TRUE;
    }
    if (isset($form['container'])) {
      $form['container']['#attributes']['class'][] = 'media-library-add-form__input-wrapper';
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for MenuLinkContentForm.
   *
   * Alters the menu_link_content_form by organizing form elements into
   * different 'details' sections.
   */
  #[Hook('form_menu_link_content_form_alter')]
  public function formMenuLinkContentFormAlter(array &$form): void {
    $form['#theme'] = ['menu_link_form'];
    $form['#attached']['library'][] = 'admin/form-two-columns';
    $form['advanced'] = [
      '#type' => 'container',
      '#weight' => 10,
      '#accordion' => TRUE,
    ];
    $form['menu_parent']['#wrapper_attributes'] = ['class' => ['accordion__item', 'entity-meta__header']];
    $form['menu_parent']['#prefix'] = '<div class="accordion">';
    $form['menu_parent']['#suffix'] = '</div>';
    $form['menu_parent']['#group'] = 'advanced';

    $form['menu_link_display_settings'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#title' => $this->t('Display settings'),
      '#attributes' => ['class' => ['entity-meta__options']],
      '#tree' => FALSE,
      '#accordion' => TRUE,
    ];
    if (!empty($form['weight'])) {
      $form['menu_link_display_settings']['weight'] = $form['weight'];
      unset($form['weight'], $form['menu_link_display_settings']['weight']['#weight']);
    }
    if (!empty($form['expanded'])) {
      $form['menu_link_display_settings']['expanded'] = $form['expanded'];
      unset($form['expanded']);
    }

    if (isset($form['description'])) {
      $form['menu_link_description'] = [
        '#type' => 'details',
        '#group' => 'advanced',
        '#title' => $this->t('Description'),
        '#attributes' => ['class' => ['entity-meta__description']],
        '#tree' => FALSE,
        '#accordion' => TRUE,
        'description' => $form['description'],
      ];
      unset($form['description']);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for MenuLinkEditForm.
   *
   * Alters the menu_link_edit form by organizing form elements into different
   * 'details' sections.
   */
  #[Hook('form_menu_link_edit_alter')]
  public function formMenuLinkEditAlter(array &$form): void {
    $this->formMenuLinkContentFormAlter($form);
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for \Drupal\node\Form\NodeForm.
   *
   * Changes vertical tabs to container.
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(array &$form): void {
    $form['#theme'] = ['node_edit_form'];
    $form['#attached']['library'][] = 'admin/form-two-columns';
    $this->ensureAdvancedSettings($form);
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_node_preview_form_select_alter')]
  public function formNodePreviewFormSelectAlter(array &$form): void {
    if (isset($form['backlink'])) {
      $form['backlink']['#options']['attributes']['class'][] = 'action-link';
      $form['backlink']['#options']['attributes']['class'][] = 'action-link--icon-chevron-left';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the system_modules form.
   */
  #[Hook('form_system_modules_alter')]
  public function formSystemModulesAlter(array &$form): void {
    if (isset($form['filters'])) {
      $form['filters']['#attributes']['class'][] = 'modules-table-filter';
      if (isset($form['filters']['text'])) {
        unset($form['filters']['text']['#title_display']);
        $form['filters']['text']['#title'] = $this->t('Filter');
      }
    }

    // Convert module links to action links.
    foreach (Element::children($form['modules']) as $key) {
      $link_key_to_action_link_type = [
        'help' => 'questionmark',
        'permissions' => 'key',
        'configure' => 'cog',
      ];
      if (isset($form['modules'][$key]['#type']) && $form['modules'][$key]['#type'] === 'details') {
        $form['modules'][$key]['#module_package_listing'] = TRUE;
        foreach (Element::children($form['modules'][$key]) as $module_key) {
          if (isset($form['modules'][$key][$module_key]['links'])) {
            foreach ($form['modules'][$key][$module_key]['links'] as $link_key => &$link) {
              if (array_key_exists($link_key, $link_key_to_action_link_type)) {
                $action_link_type = $link_key_to_action_link_type[$link_key];
                $link['#options']['attributes']['class'][] = 'action-link';
                $link['#options']['attributes']['class'][] = 'action-link--small';
                $link['#options']['attributes']['class'][] = "action-link--icon-$action_link_type";
              }
            }
          }
        }
      }
    }
  }

  /**
   * Implement hook form_system_theme_settings_alter().
   */
  #[Hook('form_system_theme_settings_alter')]
  public function formSystemThemeSettingsAlter(array &$form): void {
    if (!isset($form['config_key']['#value']) || $form['config_key']['#value'] !== 'admin.settings') {
      return;
    }
    $settings = Settings::getInstance();

    // Move default theme settings to bottom.
    $form['logo']['#open'] = FALSE;
    $form['logo']['#weight'] = 97;
    $form['favicon']['#open'] = FALSE;
    $form['favicon']['#weight'] = 98;
    $form['theme_settings']['#open'] = FALSE;
    $form['theme_settings']['#weight'] = 99;

    // General settings.
    $form['custom_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Settings'),
    ] + $settings->getSettingsForm();

    // Allow user settings.
    $form['custom_settings']['show_user_theme_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Users can override admin settings'),
      '#description' => $this->t('Expose the admin theme settings to users.'),
      '#default_value' => $settings->getDefault('show_user_theme_settings'),
    ];

    // Add handler.
    $form['#validate'][] = [__CLASS__, 'formSystemThemeSettingsAlterValidate'];
    $form['#submit'][] = [__CLASS__, 'formSystemThemeSettingsAlterSubmit'];

    // Attach custom library.
    $form['#attached']['library'][] = 'admin/settings';
  }

  /**
   * Validate theme settings.
   */
  public static function formSystemThemeSettingsAlterValidate(array &$form, FormStateInterface $form_state): void {}

  /**
   * Submit theme settings.
   */
  public static function formSystemThemeSettingsAlterSubmit(array &$form, FormStateInterface $form_state): void {}

  /**
   * Implements hook_form_FORM_ID_alter() for the user_admin_permissions form.
   */
  #[Hook('form_user_admin_permissions_alter')]
  public function formUserAdminPermissionsAlter(array &$form): void {
    if (isset($form['filters'])) {
      $form['filters']['#attributes']['class'][] = 'permissions-table-filter';
      if (isset($form['filters']['text'])) {
        unset($form['filters']['text']['#title_display']);
        $form['filters']['text']['#title'] = $this->t('Filter');
      }
    }
  }

  /**
   * Implements form_user_form_alter().
   */
  #[Hook('form_user_form_alter')]
  public function formUserFormAlter(array &$form, FormStateInterface $form_state): void {
    // If new user account, don't show settings yet.
    $formObject = $form_state->getFormObject();
    if ($formObject instanceof EntityFormInterface && $formObject->getEntity()->isNew()) {
      return;
    }

    if (Settings::getInstance()->allowUserOverrides()) {
      // Inject the settings for the dark mode feature.
      $form['admin_theme_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Admin theme settings'),
        '#open' => TRUE,
        '#weight' => 90,
      ];

      /** @var \Drupal\Core\Session\AccountInterface $account */
      $account = $form_state->getBuildInfo()['callback_object']->getEntity();
      $form['admin_theme_settings']['enable_user_settings'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable overrides'),
        '#description' => $this->t("Enables default admin theme overrides."),
        '#default_value' => Settings::getInstance()->userOverrideEnabled($account),
        '#weight' => 0,
      ];

      $form['admin_theme_settings']['user_settings'] = [
        '#type' => 'container',
        '#states' => [
          // Show if met.
          'visible' => [
            ':input[name="enable_user_settings"]' => ['checked' => TRUE],
          ],
        ],
      ] + Settings::getInstance()->getSettingsForm($account);

      // Attach custom library.
      $form['#attached']['library'][] = 'admin/settings';

      array_unshift($form['actions']['submit']['#submit'], [__CLASS__, 'userFormSubmit']);
    }
  }

  /**
   * Submit handler for the user form.
   */
  public static function userFormSubmit(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $form_state->getBuildInfo()['callback_object']->getEntity();

    $settings = \Drupal::classResolver(Settings::class);
    $enabledUserOverrides = $form_state->getValue('enable_user_settings');
    if ($enabledUserOverrides) {
      $user_settings = [
        'preset_accent_color' => $form_state->getValue('preset_accent_color'),
        'preset_focus_color' => $form_state->getValue('preset_focus_color'),
        'enable_dark_mode' => $form_state->getValue('enable_dark_mode'),
        'high_contrast_mode' => (bool) $form_state->getValue('high_contrast_mode'),
        'accent_color' => $form_state->getValue('accent_color'),
        'focus_color' => $form_state->getValue('focus_color'),
        'layout_density' => $form_state->getValue('layout_density'),
        'show_description_toggle' => $form_state->getValue('show_description_toggle'),
      ];
      $settings->setAll($user_settings, $account);
    }
    else {
      $settings->clear($account);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for views_exposed_form.
   */
  #[Hook('form_views_exposed_form_alter')]
  public function formViewsExposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $view = $form_state->getStorage()['view'];
    $view_title = $view->getTitle();

    // Add a label so screen readers can identify the purpose of the exposed
    // form without having to scan content that appears further down the page.
    $form['#attributes']['aria-label'] = $this->t('Filter the contents of the %view_title view', ['%view_title' => $view_title]);
  }

  /**
   * Implements hook_form_FORM_ID_alter() for Views UI add handler form.
   */
  #[Hook('form_views_ui_add_handler_form_alter')]
  public function formViewsUiAddHandlerFormAlter(array &$form): void {
    // Remove container-inline class to allow more control over styling.
    if (isset($form['selected']['#attributes']['class'])) {
      $form['selected']['#attributes']['class'] = array_diff($form['selected']['#attributes']['class'], ['container-inline']);
    }
    // Move all form elements in controls to its parent, this places them all in
    // the same div, which makes it possible to position them with flex styling
    // instead of floats.
    if (isset($form['override']['controls'])) {
      foreach (Element::children($form['override']['controls']) as $key) {
        $form['override']["controls_$key"] = $form['override']['controls'][$key];

        // The wrapper array for controls is removed after this loop completes.
        // The wrapper ensured that its child elements were hidden in browsers
        // without JavaScript. To replicate this functionality, the `.js-show`
        // class is added to each item previously contained in the wrapper.
        if (isset($form['override']['controls']['#id']) && $form['override']['controls']['#id'] === 'views-filterable-options-controls') {
          $form['override']["controls_$key"]['#wrapper_attributes']['class'][] = 'js-show';
        }
      }

      unset($form['override']['controls']);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the Views UI config form.
   */
  #[Hook('form_views_ui_config_item_form_alter')]
  public function formViewsUiConfigItemFormAlter(array &$form, FormStateInterface $form_state): void {
    $type = $form_state->get('type');

    if ($type === 'filter') {
      // Remove clearfix classes from several elements. They add unwanted
      // whitespace and are no longer needed because uses of `float:` in this
      // form have been removed.
      // @todo Many of the changes to classes within this conditional may not be
      //   needed or require refactoring in https://drupal.org/node/3164890
      unset($form['options']['clear_markup_start'], $form['options']['clear_markup_end']);
      if (isset($form['options']['expose_button']['#prefix'])) {
        $form['options']['expose_button']['#prefix'] = str_replace('clearfix', '', $form['options']['expose_button']['#prefix']);
      }
      if (isset($form['options']['group_button']['#prefix'])) {
        $form['options']['group_button']['#prefix'] = str_replace('clearfix', '', $form['options']['group_button']['#prefix']);
      }

      // Remove `views-(direction)-(amount)` classes, replace with
      // `views-group-box--operator`, and add a `views-config-group-region`
      // wrapper.
      if (isset($form['options']['operator']['#prefix'])) {
        foreach (['views-left-30', 'views-left-40'] as $left_class) {
          if (str_contains($form['options']['operator']['#prefix'], $left_class)) {
            $form['options']['operator']['#prefix'] = '<div class="views-config-group-region">' . str_replace($left_class, 'views-group-box--operator', $form['options']['operator']['#prefix']);
            $form['options']['value']['#suffix'] = ($form['options']['value']['#suffix'] ?? '') . '</div>';
          }
        }
      }

      // Some instances of this form input have an added wrapper that needs to
      // be removed in order to style these forms consistently.
      // @see \Drupal\views\Plugin\views\filter\InOperator::valueForm
      $wrapper_div_to_remove = '<div id="edit-options-value-wrapper">';
      if (isset($form['options']['value']['#prefix']) && str_contains($form['options']['value']['#prefix'], $wrapper_div_to_remove)) {
        $form['options']['value']['#prefix'] = str_replace($wrapper_div_to_remove, '', $form['options']['value']['#prefix']);
        $form['options']['value']['#suffix'] = preg_replace('/<\/div>/', '', $form['options']['value']['#suffix'], 1);
      }

      if (isset($form['options']['value']['#prefix'])) {
        foreach (['views-right-70', 'views-right-60'] as $right_class) {
          if (str_contains($form['options']['value']['#prefix'], $right_class)) {
            $form['options']['value']['#prefix'] = str_replace($right_class, 'views-group-box--value', $form['options']['value']['#prefix']);
          }
        }
      }

      // If the form includes a `value` field, the `.views-group-box--value` and
      // `.views-group-box` classes must be present in a wrapper div. Add them
      // here if it they are not yet present.
      if (!isset($form['options']['value']['#prefix']) || !str_contains($form['options']['value']['#prefix'], 'views-group-box--value')) {
        $prefix = $form['options']['value']['#prefix'] ?? '';
        $suffix = $form['options']['value']['#suffix'] ?? '';
        $form['options']['value']['#prefix'] = '<div class="views-group-box views-group-box--value">' . $prefix;
        $form['options']['value']['#suffix'] = $suffix . '</div>';
      }

      // If operator or value have no children, remove them from the render
      // array so their prefixes and suffixes aren't added without any content.
      foreach (['operator', 'value'] as $form_item) {
        if (isset($form['options'][$form_item]['#prefix']) && count($form['options'][$form_item]) === 2 && $form['options'][$form_item]['#suffix']) {
          unset($form['options'][$form_item]);
        }
      }
    }
  }

  /**
   * Add some major form overrides.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function stickyActionButtonsAndSidebar(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($this->isModalOrOffcanvas()) {
      $form['is_ajax_request'] = ['#weight' => -1];
      return;
    }
    if (
      str_ends_with($form_id, '_exposed_form') ||
      str_starts_with($form_id, 'views_ui_')
    ) {
      return;
    }

    // Save form types and behaviors.
    $is_content_form = Helper::isContentForm($form_state, $form_id);

    // If there are action buttons, and they should either be sticky or there
    // is a content form, where the sidebar toggle is required, prepare the
    // sticky action container for the top bar.
    if (isset($form['actions']) && (self::useStickyActionButtons($is_content_form) || $is_content_form)) {
      // Sticky action container.
      $form['gin_sticky_actions'] = [
        '#type' => 'container',
        '#weight' => -1,
        '#multilingual' => TRUE,
        '#attributes' => [
          'class' => ['gin-sticky-form-actions'],
        ],
      ];
      $form['#after_build'][] = [__CLASS__, 'formAfterBuild'];
    }

    // Sticky action buttons.
    if (self::useStickyActionButtons($is_content_form) && isset($form['actions'])) {
      // Add sticky class.
      $form['actions']['#attributes']['class'][] = 'gin-sticky-form-actions';

      // Add a class to identify modified forms.
      if (!isset($form['#attributes']['class'])) {
        $form['#attributes']['class'] = [];
      }
      elseif (is_string($form['#attributes']['class'])) {
        $form['#attributes']['class'] = [$form['#attributes']['class']];
      }
      $form['#attributes']['class'][] = 'gin--has-sticky-form-actions';

      // Assign status to gin_actions.
      $form['gin_sticky_actions']['status'] = [
        '#type' => 'container',
        '#weight' => -1,
        '#multilingual' => TRUE,
      ];

      // Only alter the status field on content forms.
      if ($is_content_form) {
        // Set form id to status field.
        if (isset($form['status']['widget']['value'])) {
          $form['status']['widget']['value']['#attributes']['form'] = $form['#id'];
          $widget_type = $form['status']['widget']['value']['#type'] ?? FALSE;
        }
        else {
          $widget_type = $form['status']['widget']['#type'] ?? FALSE;
        }
        // Only move status to status group if it is a checkbox.
        if ($widget_type === 'checkbox') {
          $form['status']['#group'] = 'status';
        }
      }

      // Helper item to move focus to sticky header.
      $form['gin_move_focus_to_sticky_bar'] = [
        '#markup' => '<a href="#" class="visually-hidden" role="button" gin-move-focus-to-sticky-bar>Moves focus to sticky header actions</a>',
        '#weight' => 999,
      ];

      // Attach library.
      $form['#attached']['library'][] = 'admin/more_actions';
    }

    // Remaining changes only apply to content forms.
    if (!$is_content_form) {
      return;
    }

    // Provide a default meta form element if not already provided.
    // @see NodeForm::form()
    $form['advanced']['#attributes']['class'][] = 'entity-meta';
    if (!isset($form['meta'])) {
      $form['meta'] = [
        '#group' => 'advanced',
        '#weight' => -10,
        '#title' => $this->t('Status'),
        '#attributes' => ['class' => ['entity-meta__header']],
        '#tree' => TRUE,
      ];
    }

    $this->ensureAdvancedSettings($form);

    // Add sidebar toggle.
    $hide_panel = $this->t('Hide sidebar panel');
    $form['gin_sticky_actions']['gin_sidebar_toggle'] = [
      '#markup' => '<a href="#toggle-sidebar" class="meta-sidebar__trigger trigger" role="button" title="' . $hide_panel . '" aria-controls="gin_sidebar"><span class="visually-hidden">' . $hide_panel . '</span></a>',
      '#weight' => 1000,
    ];
    $form['#attached']['library'][] = 'admin/sidebar';

    // Create gin_sidebar group.
    $form['gin_sidebar'] = [
      '#group' => 'meta',
      '#type' => 'container',
      '#weight' => 99,
      '#multilingual' => TRUE,
      '#attributes' => [
        'class' => [
          'gin-sidebar',
        ],
      ],
    ];
    // Copy footer over.
    $form['gin_sidebar']['footer'] = ($form['footer']) ?? [];

    // Sidebar close button.
    $close_sidebar_translation = $this->t('Close sidebar panel');
    $form['gin_sidebar']['gin_sidebar_close'] = [
      '#markup' => '<a href="#close-sidebar" class="meta-sidebar__close trigger" role="button" title="' . $close_sidebar_translation . '"><span class="visually-hidden">' . $close_sidebar_translation . '</span></a>',
    ];

    $form['gin_sidebar_overlay'] = [
      '#markup' => '<div class="meta-sidebar__overlay trigger"></div>',
    ];

    // Specify necessary node form theme and library.
    // @see gin_form_node_form_alter
    $form['#theme'] = ['node_edit_form'];
    // Attach libraries.
    $form['#attached']['library'][] = 'admin/node-form';
    $form['#attached']['library'][] = 'admin/edit_form';

    // Add a class that allows the logic in edit_form.js to identify the form.
    $form['#attributes']['class'][] = 'gin-node-edit-form';

    // If not logged in hide changed and author node info on add forms.
    $not_logged_in = $this->currentUser->isAnonymous();
    $route = $this->routeMatch->getRouteName();

    if ($not_logged_in && $route === 'node.add') {
      unset($form['meta']['changed'], $form['meta']['author']);
    }
  }

  /**
   * Determines the feature flag to use for sticky action buttons.
   *
   * @param bool $is_content_form
   *   TRUE if this should return the flag for content forms, FALSE if it should
   *   return the flag for any form.
   *
   * @return bool
   *   TRUE if core_admin_theme_use_sticky_action_buttons is enabled, FALSE
   *   otherwise.
   */
  private static function useStickyActionButtons(bool $is_content_form = TRUE): bool {
    $flag = CoreSettings::get('core_admin_theme_use_sticky_action_buttons', 'never');
    return $is_content_form ?
      $flag === 'content_forms' || $flag === 'always' :
      $flag === 'always';
  }

  /**
   * Check the context we're in.
   *
   * Checks if the form is in either a modal or an off-canvas dialog.
   */
  private function isModalOrOffcanvas(): bool {
    $wrapper_format = $this->getRequestWrapperFormat() ?? '';
    return str_contains($wrapper_format, 'drupal_modal') ||
      str_contains($wrapper_format, 'drupal_dialog');
  }

  /**
   * Helper function to remember the form actions after form has been built.
   */
  public static function formAfterBuild(array $form): array {
    if (self::useStickyActionButtons()) {
      // Allowlist for visible actions.
      $includes = ['save', 'submit', 'preview'];

      // Build actions.
      foreach (Element::children($form['actions']) as $key) {
        $button = ($form['actions'][$key]) ?? [];

        if (!($button['#access'] ?? TRUE)) {
          continue;
        }

        $navigation_enabled = \Drupal::service('module_handler')->moduleExists('navigation');
        if ($navigation_enabled) {
          $form['gin_sticky_actions']['actions'][$key] = $button;
        }

        // The media_type_add_form form is a special case.
        // @see https://www.drupal.org/project/gin/issues/3534385
        // @see \Drupal\media\MediaTypeForm::actions
        if ((isset($button['#type']) && $button['#type'] === 'submit') || $form['#form_id'] === 'media_type_add_form') {
          // Update button.
          $button['#attributes']['id'] = 'gin-sticky-' . $button['#id'];
          $button['#attributes']['form'] = $form['#id'];
          $button['#attributes']['data-drupal-selector'] = 'gin-sticky-' . $button['#attributes']['data-drupal-selector'];
          $button['#attributes']['data-gin-sticky-form-selector'] = $button['#attributes']['data-drupal-selector'];

          // Add the button to the form actions array.
          if (!empty($button['#gin_action_item']) || $navigation_enabled || in_array($key, $includes, TRUE)) {
            $form['gin_sticky_actions']['actions'][$key] = $button;
          }
        }
      }
    }

    Helper::formActions($form['gin_sticky_actions'] ?? NULL);
    unset($form['gin_sticky_actions']);

    return $form;
  }

  /**
   * Ensure correct settings for advanced, meta and revision form elements.
   *
   * @param array $form
   *   The form.
   */
  private function ensureAdvancedSettings(array &$form): void {
    $form['advanced']['#type'] = 'container';
    $form['advanced']['#accordion'] = TRUE;
    $form['meta']['#type'] = 'container';
    $form['meta']['#access'] = TRUE;

    $form['revision_information']['#type'] = 'container';
    $form['revision_information']['#group'] = 'meta';
    $form['revision_information']['#attributes']['class'][] = 'entity-meta__revision';
  }

}
