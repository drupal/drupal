<?php

namespace Drupal\system\Form;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Displays theme configuration for entire site and individual themes.
 *
 * @internal
 */
class ThemeSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * An array of configuration names that should be editable.
   *
   * @var array
   */
  protected $editableConfig = [];

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a ThemeSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler instance to use.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser instance to use.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, MimeTypeGuesserInterface $mime_type_guesser, ThemeManagerInterface $theme_manager, FileSystemInterface $file_system = NULL) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->themeManager = $theme_manager;
    if (!$file_system) {
      @trigger_error('The file_system service must be passed to ThemeSettingsForm::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/3006851.', E_USER_DEPRECATED);
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('theme_handler'),
      $container->get('file.mime_type.guesser'),
      $container->get('theme.manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_theme_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return $this->editableConfig;
  }

  /**
   * {@inheritdoc}
   *
   * @param string $theme
   *   The theme name.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = '') {
    $form = parent::buildForm($form, $form_state);

    $themes = $this->themeHandler->listInfo();

    // Default settings are defined in theme_get_setting() in includes/theme.inc
    if ($theme) {
      if (!$this->themeHandler->hasUi($theme)) {
        throw new NotFoundHttpException();
      }
      $var = 'theme_' . $theme . '_settings';
      $config_key = $theme . '.settings';
      $themes = $this->themeHandler->listInfo();
      $features = $themes[$theme]->info['features'];
    }
    else {
      $var = 'theme_settings';
      $config_key = 'system.theme.global';
    }
    // @todo this is pretty meaningless since we're using theme_get_settings
    //   which means overrides can bleed into active config here. Will be fixed
    //   by https://www.drupal.org/node/2402467.
    $this->editableConfig = [$config_key];

    $form['var'] = [
      '#type' => 'hidden',
      '#value' => $var,
    ];
    $form['config_key'] = [
      '#type' => 'hidden',
      '#value' => $config_key,
    ];

    // Toggle settings
    $toggles = [
      'node_user_picture' => t('User pictures in posts'),
      'comment_user_picture' => t('User pictures in comments'),
      'comment_user_verification' => t('User verification status in comments'),
      'favicon' => t('Shortcut icon'),
    ];

    // Some features are not always available
    $disabled = [];
    if (!user_picture_enabled()) {
      $disabled['toggle_node_user_picture'] = TRUE;
      $disabled['toggle_comment_user_picture'] = TRUE;
    }
    if (!$this->moduleHandler->moduleExists('comment')) {
      $disabled['toggle_comment_user_picture'] = TRUE;
      $disabled['toggle_comment_user_verification'] = TRUE;
    }

    $form['theme_settings'] = [
      '#type' => 'details',
      '#title' => t('Page element display'),
      '#open' => TRUE,
    ];
    foreach ($toggles as $name => $title) {
      if ((!$theme) || in_array($name, $features)) {
        $form['theme_settings']['toggle_' . $name] = ['#type' => 'checkbox', '#title' => $title, '#default_value' => theme_get_setting('features.' . $name, $theme)];
        // Disable checkboxes for features not supported in the current configuration.
        if (isset($disabled['toggle_' . $name])) {
          $form['theme_settings']['toggle_' . $name]['#disabled'] = TRUE;
        }
      }
    }

    if (!Element::children($form['theme_settings'])) {
      // If there is no element in the theme settings details then do not show
      // it -- but keep it in the form if another module wants to alter.
      $form['theme_settings']['#access'] = FALSE;
    }

    // Logo settings, only available when file.module is enabled.
    if ((!$theme || in_array('logo', $features)) && $this->moduleHandler->moduleExists('file')) {
      $form['logo'] = [
        '#type' => 'details',
        '#title' => t('Logo image'),
        '#open' => TRUE,
      ];
      $form['logo']['default_logo'] = [
        '#type' => 'checkbox',
        '#title' => t('Use the logo supplied by the theme'),
        '#default_value' => theme_get_setting('logo.use_default', $theme),
        '#tree' => FALSE,
      ];
      $form['logo']['settings'] = [
        '#type' => 'container',
        '#states' => [
          // Hide the logo settings when using the default logo.
          'invisible' => [
            'input[name="default_logo"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['logo']['settings']['logo_path'] = [
        '#type' => 'textfield',
        '#title' => t('Path to custom logo'),
        '#default_value' => theme_get_setting('logo.path', $theme),
      ];
      $form['logo']['settings']['logo_upload'] = [
        '#type' => 'file',
        '#title' => t('Upload logo image'),
        '#maxlength' => 40,
        '#description' => t("If you don't have direct file access to the server, use this field to upload your logo."),
        '#upload_validators' => [
          'file_validate_is_image' => [],
        ],
      ];
    }

    if (((!$theme) || in_array('favicon', $features)) && $this->moduleHandler->moduleExists('file')) {
      $form['favicon'] = [
        '#type' => 'details',
        '#title' => t('Favicon'),
        '#open' => TRUE,
        '#description' => t("Your shortcut icon, or favicon, is displayed in the address bar and bookmarks of most browsers."),
        '#states' => [
          // Hide the shortcut icon settings fieldset when shortcut icon display
          // is disabled.
          'invisible' => [
            'input[name="toggle_favicon"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['favicon']['default_favicon'] = [
        '#type' => 'checkbox',
        '#title' => t('Use the favicon supplied by the theme'),
        '#default_value' => theme_get_setting('favicon.use_default', $theme),
      ];
      $form['favicon']['settings'] = [
        '#type' => 'container',
        '#states' => [
          // Hide the favicon settings when using the default favicon.
          'invisible' => [
            'input[name="default_favicon"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['favicon']['settings']['favicon_path'] = [
        '#type' => 'textfield',
        '#title' => t('Path to custom icon'),
        '#default_value' => theme_get_setting('favicon.path', $theme),
      ];
      $form['favicon']['settings']['favicon_upload'] = [
        '#type' => 'file',
        '#title' => t('Upload favicon image'),
        '#description' => t("If you don't have direct file access to the server, use this field to upload your shortcut icon."),
        '#upload_validators' => [
          'file_validate_extensions' => [
            'ico png gif jpg jpeg apng svg',
          ],
        ],
      ];
    }

    // Inject human-friendly values and form element descriptions for logo and
    // favicon.
    foreach (['logo' => 'logo.svg', 'favicon' => 'favicon.ico'] as $type => $default) {
      if (isset($form[$type]['settings'][$type . '_path'])) {
        $element = &$form[$type]['settings'][$type . '_path'];

        // If path is a public:// URI, display the path relative to the files
        // directory; stream wrappers are not end-user friendly.
        $original_path = $element['#default_value'];
        $friendly_path = NULL;

        if (StreamWrapperManager::getScheme($original_path) == 'public') {
          $friendly_path = StreamWrapperManager::getTarget($original_path);
          $element['#default_value'] = $friendly_path;
        }

        // Prepare local file path for description.
        if ($original_path && isset($friendly_path)) {
          $local_file = strtr($original_path, ['public:/' => PublicStream::basePath()]);
        }
        elseif ($theme) {
          $local_file = drupal_get_path('theme', $theme) . '/' . $default;
        }
        else {
          $local_file = $this->themeManager->getActiveTheme()->getPath() . '/' . $default;
        }

        $element['#description'] = t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>, or <code>@local-file</code>.', [
          '@implicit-public-file' => isset($friendly_path) ? $friendly_path : $default,
          '@explicit-file' => StreamWrapperManager::getScheme($original_path) !== FALSE ? $original_path : 'public://' . $default,
          '@local-file' => $local_file,
        ]);
      }
    }

    if ($theme) {
      // Call engine-specific settings.
      $function = $themes[$theme]->prefix . '_engine_settings';
      if (function_exists($function)) {
        $form['engine_specific'] = [
          '#type' => 'details',
          '#title' => t('Theme-engine-specific settings'),
          '#open' => TRUE,
          '#description' => t('These settings only exist for the themes based on the %engine theme engine.', ['%engine' => $themes[$theme]->prefix]),
        ];
        $function($form, $form_state);
      }

      // Create a list which includes the current theme and all its base themes.
      if (isset($themes[$theme]->base_themes)) {
        $theme_keys = array_keys($themes[$theme]->base_themes);
        $theme_keys[] = $theme;
      }
      else {
        $theme_keys = [$theme];
      }

      // Save the name of the current theme (if any), so that we can temporarily
      // override the current theme and allow theme_get_setting() to work
      // without having to pass the theme name to it.
      $default_active_theme = $this->themeManager->getActiveTheme();
      $default_theme = $default_active_theme->getName();
      /** @var \Drupal\Core\Theme\ThemeInitialization $theme_initialization */
      $theme_initialization = \Drupal::service('theme.initialization');
      $this->themeManager->setActiveTheme($theme_initialization->getActiveThemeByName($theme));

      // Process the theme and all its base themes.
      foreach ($theme_keys as $theme) {
        // Include the theme-settings.php file.
        $theme_path = drupal_get_path('theme', $theme);
        $theme_settings_file = $theme_path . '/theme-settings.php';
        $theme_file = $theme_path . '/' . $theme . '.theme';
        $filenames = [$theme_settings_file, $theme_file];
        foreach ($filenames as $filename) {
          if (file_exists($filename)) {
            require_once $filename;

            // The file must be required for the cached form too.
            $files = $form_state->getBuildInfo()['files'];
            if (!in_array($filename, $files)) {
              $files[] = $filename;
            }
            $form_state->addBuildInfo('files', $files);
          }
        }

        // Call theme-specific settings.
        $function = $theme . '_form_system_theme_settings_alter';
        if (function_exists($function)) {
          $function($form, $form_state);
        }
      }

      // Restore the original current theme.
      if (isset($default_theme)) {
        $this->themeManager->setActiveTheme($default_active_theme);
      }
      else {
        $this->themeManager->resetActiveTheme();
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('file')) {

      // Check for a new uploaded logo.
      if (isset($form['logo'])) {
        $file = _file_save_upload_from_form($form['logo']['settings']['logo_upload'], $form_state, 0);
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('logo_upload', $file);
        }
      }

      // Check for a new uploaded favicon.
      if (isset($form['favicon'])) {
        $file = _file_save_upload_from_form($form['favicon']['settings']['favicon_upload'], $form_state, 0);
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('favicon_upload', $file);
        }
      }

      // When intending to use the default logo, unset the logo_path.
      if ($form_state->getValue('default_logo')) {
        $form_state->unsetValue('logo_path');
      }

      // When intending to use the default favicon, unset the favicon_path.
      if ($form_state->getValue('default_favicon')) {
        $form_state->unsetValue('favicon_path');
      }

      // If the user provided a path for a logo or favicon file, make sure a file
      // exists at that path.
      if ($form_state->getValue('logo_path')) {
        $path = $this->validatePath($form_state->getValue('logo_path'));
        if (!$path) {
          $form_state->setErrorByName('logo_path', $this->t('The custom logo path is invalid.'));
        }
      }
      if ($form_state->getValue('favicon_path')) {
        $path = $this->validatePath($form_state->getValue('favicon_path'));
        if (!$path) {
          $form_state->setErrorByName('favicon_path', $this->t('The custom favicon path is invalid.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config_key = $form_state->getValue('config_key');
    $this->editableConfig = [$config_key];
    $config = $this->config($config_key);

    // Exclude unnecessary elements before saving.
    $form_state->cleanValues();
    $form_state->unsetValue('var');
    $form_state->unsetValue('config_key');

    $values = $form_state->getValues();

    // If the user uploaded a new logo or favicon, save it to a permanent location
    // and use it in place of the default theme-provided file.
    $default_scheme = $this->config('system.file')->get('default_scheme');
    try {
      if (!empty($values['logo_upload'])) {
        $filename = $this->fileSystem->copy($values['logo_upload']->getFileUri(), $default_scheme . '://');
        $values['default_logo'] = 0;
        $values['logo_path'] = $filename;
      }
    }
    catch (FileException $e) {
      // Ignore.
    }
    try {
      if (!empty($values['favicon_upload'])) {
        $filename = $this->fileSystem->copy($values['favicon_upload']->getFileUri(), $default_scheme . '://');
        $values['default_favicon'] = 0;
        $values['favicon_path'] = $filename;
        $values['toggle_favicon'] = 1;
      }
    }
    catch (FileException $e) {
      // Ignore.
    }
    unset($values['logo_upload']);
    unset($values['favicon_upload']);

    // If the user entered a path relative to the system files directory for
    // a logo or favicon, store a public:// URI so the theme system can handle it.
    if (!empty($values['logo_path'])) {
      $values['logo_path'] = $this->validatePath($values['logo_path']);
    }
    if (!empty($values['favicon_path'])) {
      $values['favicon_path'] = $this->validatePath($values['favicon_path']);
    }

    if (empty($values['default_favicon']) && !empty($values['favicon_path'])) {
      $values['favicon_mimetype'] = $this->mimeTypeGuesser->guess($values['favicon_path']);
    }

    theme_settings_convert_to_config($values, $config)->save();
  }

  /**
   * Helper function for the system_theme_settings form.
   *
   * Attempts to validate normal system paths, paths relative to the public files
   * directory, or stream wrapper URIs. If the given path is any of the above,
   * returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   *
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    // Absolute local file paths are invalid.
    if ($this->fileSystem->realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (StreamWrapperManager::getScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

}
