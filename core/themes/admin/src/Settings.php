<?php

declare(strict_types=1);

namespace Drupal\admin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to handle overridden user settings.
 */
final class Settings implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Settings constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver.
   * @param \Drupal\user\UserDataInterface|null $userData
   *   The user data service.
   * @param \Drupal\Core\Extension\ThemeSettingsProvider $themeSettingsProvider
   *   The theme settings provider.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
    protected ClassResolverInterface $classResolver,
    protected ?UserDataInterface $userData,
    protected ThemeSettingsProvider $themeSettingsProvider,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Settings {
    return new Settings(
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('class_resolver'),
      $container->get('user.data', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get(ThemeSettingsProvider::class),
    );
  }

  /**
   * Gets the admin settings.
   *
   * @return \Drupal\admin\Settings
   *   The admin settings.
   */
  public static function getInstance(): Settings {
    static $settings;
    if ($settings === NULL) {
      $settings = \Drupal::classResolver(Settings::class);
    }
    return $settings;
  }

  /**
   * Get the setting for the current user.
   *
   * @param string $name
   *   The name of the setting.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object. Current user if NULL.
   *
   * @return array|bool|mixed|null
   *   The current value.
   */
  public function get(string $name, ?AccountInterface $account = NULL): mixed {
    $value = NULL;
    if (!$account) {
      $account = $this->currentUser;
    }
    if ($this->userOverrideEnabled($account)) {
      $settings = $this->userData->get('admin', $account->id(), 'settings');
      $value = $settings[$name] ?? $this->userData->get('admin', $account->id(), $name);
    }
    if (is_null($value)) {
      $admin_theme = $this->getAdminTheme();
      $value = $this->themeSettingsProvider->getSetting($name, $admin_theme);
    }
    return $value;
  }

  /**
   * Get the default setting from theme.
   *
   * @param string $name
   *   The name of the setting.
   *
   * @return array|bool|mixed|null
   *   The current value.
   */
  public function getDefault(string $name): mixed {
    $admin_theme = $this->getAdminTheme();
    return $this->themeSettingsProvider->getSetting($name, $admin_theme);
  }

  /**
   * Set user overrides.
   *
   * @param array $settings
   *   The user specific theme settings.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object. Current user if NULL.
   */
  public function setAll(array $settings, ?AccountInterface $account = NULL): void {
    if (!$account || !$this->userData) {
      $account = $this->currentUser;
    }
    // All settings are deleted to remove legacy settings.
    $this->userData->delete('admin', $account->id());
    $this->userData->set('admin', $account->id(), 'enable_user_settings', TRUE);
    $this->userData->set('admin', $account->id(), 'settings', $settings);
  }

  /**
   * Clears all admin settings for the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object. Current user if NULL.
   */
  public function clear(?AccountInterface $account = NULL): void {
    if (!$account || !$this->userData) {
      $account = $this->currentUser;
    }
    $this->userData->delete('admin', $account->id());
  }

  /**
   * Determine if user overrides are allowed.
   *
   * @return bool
   *   TRUE, if the theme settings allow to be overridden by the user, FALSE
   *   otherwise.
   */
  public function allowUserOverrides(): bool {
    $admin_theme = $this->getAdminTheme();
    return $this->themeSettingsProvider->getSetting('show_user_theme_settings', $admin_theme) ?? FALSE;
  }

  /**
   * Determine if the user enabled overrides.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object. Current user if NULL.
   *
   * @return bool
   *   TRUE, if the user has overridden theme settings, FALSE otherwise.
   */
  public function userOverrideEnabled(?AccountInterface $account = NULL): bool {
    $overrides = &drupal_static(__CLASS__ . '_' . __METHOD__, []);

    if (!$account || !$this->userData) {
      $account = $this->currentUser;
    }

    if (!isset($overrides[$account->id()])) {
      $overrides[$account->id()] = $this->allowUserOverrides()
        && $this->userData->get('admin', $account->id(), 'enable_user_settings');
    }

    return $overrides[$account->id()];
  }

  /**
   * Check if the user setting overrides the global setting.
   *
   * @param string $name
   *   Name of the setting to check.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object. Current user if NULL.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function overridden(string $name, ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }
    $admin_theme = $this->getAdminTheme();
    return $this->themeSettingsProvider->getSetting($name, $admin_theme) !== $this->get($name, $account);
  }

  /**
   * Return the active administration theme.
   *
   * @return string
   *   The active administration theme name.
   */
  private function getAdminTheme(): string {
    $admin_theme = $this->configFactory->get('system.theme')->get('admin');
    if (empty($admin_theme)) {
      $admin_theme = $this->configFactory->get('system.theme')->get('default');
    }
    return $admin_theme;
  }

  /**
   * Build the settings form for the theme.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account object.
   *
   * @return array
   *   The theme setting form elements.
   */
  public function getSettingsForm(?AccountInterface $account = NULL): array {
    $experimental_label = ' <span class="gin-experimental-flag">Experimental</span>';
    $beta_label = ' <span class="gin-beta-flag">Beta</span>';

    $form['enable_dark_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Appearance'),
      '#description' => $this->t('Enables dark mode for the administration interface.'),
      '#default_value' => (string) ($account ? $this->get('enable_dark_mode', $account) : $this->getDefault('enable_dark_mode')),
      '#options' => [
        0 => $this->t('Light'),
        1 => $this->t('Dark'),
        'auto' => $this->t('Auto'),
      ],
    ];

    // Accent color setting.
    $presets = Helper::accentColors();
    $options = array_map(static function ($preset) {
      return $preset['label'];
    }, $presets);
    $form['preset_accent_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accent color'),
      '#default_value' => $account ? $this->get('preset_accent_color', $account) : $this->getDefault('preset_accent_color'),
      '#options' => $options,
      '#after_build' => [[Helper::class, 'accentRadios']],
    ];

    // Accent color group.
    $form['accent_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Accent color'),
      '#description' => $this->t('Use with caution, values should meet a11y criteria.'),
      '#states' => [
        // Show if met.
        'visible' => [
          ':input[name="preset_accent_color"]' => ['value' => 'custom'],
        ],
      ],
    ];

    // Main Accent color setting.
    $form['accent_color'] = [
      '#type' => 'textfield',
      '#placeholder' => '#777777',
      '#maxlength' => 7,
      '#size' => 7,
      '#title' => $this->t('Custom Accent color'),
      '#title_display' => 'invisible',
      '#default_value' => $account ? $this->get('accent_color', $account) : $this->getDefault('accent_color'),
      '#group' => 'accent_group',
      '#attributes' => [
        'pattern' => '^#[a-fA-F0-9]{6}',
      ],
    ];

    // Accent color picker (helper field).
    $form['accent_group']['accent_picker'] = [
      '#type' => 'color',
      '#placeholder' => '#777777',
      '#default_value' => $account ? $this->get('accent_color', $account) : $this->getDefault('accent_color'),
      '#process' => [[__CLASS__, 'processColorPicker']],
    ];

    // Focus color setting.
    $form['preset_focus_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Focus color'),
      '#default_value' => $account ? $this->get('preset_focus_color', $account) : $this->getDefault('preset_focus_color'),
      '#options' => [
        'gin' => $this->t('Admin Focus color (Default)'),
        'green' => $this->t('Green'),
        'claro' => $this->t('Claro Green'),
        'orange' => $this->t('Orange'),
        'dark' => $this->t('Neutral'),
        'accent' => $this->t('Same as Accent color'),
        'custom' => $this->t('Custom'),
      ],
    ];

    // Focus color group.
    $form['focus_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Focus color') . $beta_label,
      '#description' => $this->t('Use with caution, values should meet a11y criteria.'),
      '#states' => [
        // Show if met.
        'visible' => [
          ':input[name="preset_focus_color"]' => ['value' => 'custom'],
        ],
      ],
    ];

    // Focus color picker (helper).
    $form['focus_group']['focus_picker'] = [
      '#type' => 'color',
      '#placeholder' => '#777777',
      '#default_value' => $account ? $this->get('focus_color', $account) : $this->getDefault('focus_color'),
      '#process' => [[__CLASS__, 'processColorPicker']],
    ];

    // Custom Focus color setting.
    $form['focus_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Focus color') . $beta_label,
      '#title_display' => 'invisible',
      '#placeholder' => '#777777',
      '#maxlength' => 7,
      '#size' => 7,
      '#default_value' => $account ? $this->get('focus_color', $account) : $this->getDefault('focus_color'),
      '#group' => 'focus_group',
      '#attributes' => [
        'pattern' => '^#[a-fA-F0-9]{6}',
      ],
    ];

    // High contrast mode.
    $form['high_contrast_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Increase contrast') . $experimental_label,
      '#description' => $this->t('Enables high contrast mode.'),
      '#default_value' => $account ? $this->get('high_contrast_mode', $account) : $this->getDefault('high_contrast_mode'),
    ];

    // Layout density setting.
    $form['layout_density'] = [
      '#type' => 'radios',
      '#title' => $this->t('Layout density'),
      '#description' => $this->t('Changes the layout density for tables in the admin interface.'),
      '#default_value' => (string) ($account ? $this->get('layout_density', $account) : $this->getDefault('layout_density')),
      '#options' => [
        'default' => $this->t('Default'),
        'medium' => $this->t('Compact'),
        'small' => $this->t('Narrow'),
      ],
    ];

    // Description toggle.
    $form['show_description_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable form description toggle'),
      '#description' => $this->t('Show a help icon to show/hide form descriptions on content forms.'),
      '#default_value' => $account ? $this->get('show_description_toggle', $account) : $this->getDefault('show_description_toggle'),
    ];

    if (!$account) {
      foreach ($form as $key => $element) {
        $form[$key]['#after_build'][] = [__CLASS__, 'overriddenSettingByUser'];
      }
    }

    return $form;
  }

  /**
   * After build callback to modify the description if a setting is overwritten.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the new description.
   */
  public static function overriddenSettingByUser(array $element): array {
    $settings = self::getInstance();
    // Check if this is overridden by the logged in user.
    if ($element && isset($element['#name']) && $settings->overridden($element['#name'])) {
      $userEditUrl = Url::fromRoute('entity.user.edit_form', ['user' => \Drupal::currentUser()->id()])->toString();

      $value = $settings->get($element['#name']);
      if ($element['#type'] === 'radios' || $element['#type'] === 'select') {
        $value = $element['#options'][$value];
      }
      if ($element['#type'] === 'checkbox') {
        $value = $value ? t('Enabled') : t('Disabled');
      }

      $element += ['#description' => ''];
      $element['#description'] .= '<span class="form-item__warning">' .
        t('This setting is overridden by the <a href=":editUrl">current user</a>. @title: %value',
          [
            '@title' => $element['#title'],
            '%value' => $value,
            ':editUrl' => $userEditUrl,
          ]) . '</span>';
    }

    return $element;
  }

  /**
   * Unset color picker fields.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element.
   */
  public static function processColorPicker(array $element, FormStateInterface $form_state): array {
    $keys = $form_state->getCleanValueKeys();
    $form_state->setCleanValueKeys(array_merge($keys, $element['#parents']));

    return $element;
  }

}
