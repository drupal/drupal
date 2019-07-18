<?php

namespace Drupal\ckeditor\Plugin\Editor;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a CKEditor-based text editor for Drupal.
 *
 * @Editor(
 *   id = "ckeditor",
 *   label = @Translation("CKEditor"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 */
class CKEditor extends EditorBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler to invoke hooks on.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CKEditor plugin manager.
   *
   * @var \Drupal\ckeditor\CKEditorPluginManager
   */
  protected $ckeditorPluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a \Drupal\ckeditor\Plugin\Editor\CKEditor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ckeditor\CKEditorPluginManager $ckeditor_plugin_manager
   *   The CKEditor plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CKEditorPluginManager $ckeditor_plugin_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ckeditorPluginManager = $ckeditor_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ckeditor.plugin'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'toolbar' => [
        'rows' => [
          // Button groups.
          [
            [
              'name' => $this->t('Formatting'),
              'items' => ['Bold', 'Italic'],
            ],
            [
              'name' => $this->t('Links'),
              'items' => ['DrupalLink', 'DrupalUnlink'],
            ],
            [
              'name' => $this->t('Lists'),
              'items' => ['BulletedList', 'NumberedList'],
            ],
            [
              'name' => $this->t('Media'),
              'items' => ['Blockquote', 'DrupalImage'],
            ],
            [
              'name' => $this->t('Tools'),
              'items' => ['Source'],
            ],
          ],
        ],
      ],
      'plugins' => ['language' => ['language_list' => 'un']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $editor = $form_state->get('editor');
    $settings = $editor->getSettings();

    $ckeditor_settings_toolbar = [
      '#theme' => 'ckeditor_settings_toolbar',
      '#editor' => $editor,
      '#plugins' => $this->ckeditorPluginManager->getButtons(),
    ];
    $form['toolbar'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['ckeditor/drupal.ckeditor.admin'],
        'drupalSettings' => [
          'ckeditor' => [
            'toolbarAdmin' => (string) $this->renderer->renderPlain($ckeditor_settings_toolbar),
          ],
        ],
      ],
      '#attributes' => ['class' => ['ckeditor-toolbar-configuration']],
    ];

    $form['toolbar']['button_groups'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Toolbar buttons'),
      '#default_value' => json_encode($settings['toolbar']['rows']),
      '#attributes' => ['class' => ['ckeditor-toolbar-textarea']],
    ];

    // CKEditor plugin settings, if any.
    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('CKEditor plugin settings'),
      '#attributes' => [
        'id' => 'ckeditor-plugin-settings',
      ],
    ];
    $this->ckeditorPluginManager->injectPluginSettingsForm($form, $form_state, $editor);
    if (count(Element::children($form['plugins'])) === 0) {
      unset($form['plugins']);
      unset($form['plugin_settings']);
    }

    // Hidden CKEditor instance. We need a hidden CKEditor instance with all
    // plugins enabled, so we can retrieve CKEditor's per-feature metadata (on
    // which tags, attributes, styles and classes are enabled). This metadata is
    // necessary for certain filters' (for instance, the html_filter filter)
    // settings to be updated accordingly.
    // Get a list of all external plugins and their corresponding files.
    $plugins = array_keys($this->ckeditorPluginManager->getDefinitions());
    $all_external_plugins = [];
    foreach ($plugins as $plugin_id) {
      $plugin = $this->ckeditorPluginManager->createInstance($plugin_id);
      if (!$plugin->isInternal()) {
        $all_external_plugins[$plugin_id] = $plugin->getFile();
      }
    }
    // Get a list of all buttons that are provided by all plugins.
    $all_buttons = array_reduce($this->ckeditorPluginManager->getButtons(), function ($result, $item) {
      return array_merge($result, array_keys($item));
    }, []);
    // Build a fake Editor object, which we'll use to generate JavaScript
    // settings for this fake Editor instance.
    $fake_editor = Editor::create([
      'format' => $editor->id(),
      'editor' => 'ckeditor',
      'settings' => [
        // Single toolbar row, single button group, all existing buttons.
        'toolbar' => [
         'rows' => [
           0 => [
             0 => [
               'name' => 'All existing buttons',
               'items' => $all_buttons,
             ],
           ],
         ],
        ],
        'plugins' => $settings['plugins'],
      ],
    ]);
    $config = $this->getJSSettings($fake_editor);
    // Remove the ACF configuration that is generated based on filter settings,
    // because otherwise we cannot retrieve per-feature metadata.
    unset($config['allowedContent']);
    $form['hidden_ckeditor'] = [
      '#markup' => '<div id="ckeditor-hidden" class="hidden"></div>',
      '#attached' => [
        'drupalSettings' => ['ckeditor' => ['hiddenCKEditorConfig' => $config]],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Modify the toolbar settings by reference. The values in
    // $form_state->getValue(array('editor', 'settings')) will be saved directly
    // by editor_form_filter_admin_format_submit().
    $toolbar_settings = &$form_state->getValue(['editor', 'settings', 'toolbar']);

    // The rows key is not built into the form structure, so decode the button
    // groups data into this new key and remove the button_groups key.
    $toolbar_settings['rows'] = json_decode($toolbar_settings['button_groups'], TRUE);
    unset($toolbar_settings['button_groups']);

    // Remove the plugin settings' vertical tabs state; no need to save that.
    if ($form_state->hasValue(['editor', 'settings', 'plugins'])) {
      $form_state->unsetValue(['editor', 'settings', 'plugin_settings']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $settings = [];

    // Get the settings for all enabled plugins, even the internal ones.
    $enabled_plugins = array_keys($this->ckeditorPluginManager->getEnabledPluginFiles($editor, TRUE));
    foreach ($enabled_plugins as $plugin_id) {
      $plugin = $this->ckeditorPluginManager->createInstance($plugin_id);
      $settings += $plugin->getConfig($editor);
    }

    // Fall back on English if no matching language code was found.
    $display_langcode = 'en';

    // Map the interface language code to a CKEditor translation if interface
    // translation is enabled.
    if ($this->moduleHandler->moduleExists('locale')) {
      $ckeditor_langcodes = $this->getLangcodes();
      $language_interface = $this->languageManager->getCurrentLanguage();
      if (isset($ckeditor_langcodes[$language_interface->getId()])) {
        $display_langcode = $ckeditor_langcodes[$language_interface->getId()];
      }
    }

    // Next, set the most fundamental CKEditor settings.
    $external_plugin_files = $this->ckeditorPluginManager->getEnabledPluginFiles($editor);
    $settings += [
      'toolbar' => $this->buildToolbarJSSetting($editor),
      'contentsCss' => $this->buildContentsCssJSSetting($editor),
      'extraPlugins' => implode(',', array_keys($external_plugin_files)),
      'language' => $display_langcode,
      // Configure CKEditor to not load styles.js. The StylesCombo plugin will
      // set stylesSet according to the user's settings, if the "Styles" button
      // is enabled. We cannot get rid of this until CKEditor will stop loading
      // styles.js by default.
      // See http://dev.ckeditor.com/ticket/9992#comment:9.
      'stylesSet' => FALSE,
    ];

    // Finally, set Drupal-specific CKEditor settings.
    $root_relative_file_url = function ($uri) {
      return file_url_transform_relative(file_create_url($uri));
    };
    $settings += [
      'drupalExternalPlugins' => array_map($root_relative_file_url, $external_plugin_files),
    ];

    // Parse all CKEditor plugin JavaScript files for translations.
    if ($this->moduleHandler->moduleExists('locale')) {
      locale_js_translate(array_values($external_plugin_files));
    }

    ksort($settings);

    return $settings;
  }

  /**
   * Returns a list of language codes supported by CKEditor.
   *
   * @return array
   *   An associative array keyed by language codes.
   */
  public function getLangcodes() {
    // Cache the file system based language list calculation because this would
    // be expensive to calculate all the time. The cache is cleared on core
    // upgrades which is the only situation the CKEditor file listing should
    // change.
    $langcode_cache = \Drupal::cache()->get('ckeditor.langcodes');
    if (!empty($langcode_cache)) {
      $langcodes = $langcode_cache->data;
    }
    if (empty($langcodes)) {
      $langcodes = [];
      // Collect languages included with CKEditor based on file listing.
      $files = scandir('core/assets/vendor/ckeditor/lang');
      foreach ($files as $file) {
        if ($file[0] !== '.' && preg_match('/\.js$/', $file)) {
          $langcode = basename($file, '.js');
          $langcodes[$langcode] = $langcode;
        }
      }
      \Drupal::cache()->set('ckeditor.langcodes', $langcodes);
    }

    // Get language mapping if available to map to Drupal language codes.
    // This is configurable in the user interface and not expensive to get, so
    // we don't include it in the cached language list.
    $language_mappings = $this->moduleHandler->moduleExists('language') ? language_get_browser_drupal_langcode_mappings() : [];
    foreach ($langcodes as $langcode) {
      // If this language code is available in a Drupal mapping, use that to
      // compute a possibility for matching from the Drupal langcode to the
      // CKEditor langcode.
      // For instance, CKEditor uses the langcode 'no' for Norwegian, Drupal
      // uses 'nb'. This would then remove the 'no' => 'no' mapping and replace
      // it with 'nb' => 'no'. Now Drupal knows which CKEditor translation to
      // load.
      if (isset($language_mappings[$langcode]) && !isset($langcodes[$language_mappings[$langcode]])) {
        $langcodes[$language_mappings[$langcode]] = $langcode;
        unset($langcodes[$langcode]);
      }
    }

    return $langcodes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $libraries = [
      'ckeditor/drupal.ckeditor',
    ];

    // Get the required libraries for any enabled plugins.
    $enabled_plugins = array_keys($this->ckeditorPluginManager->getEnabledPluginFiles($editor));
    foreach ($enabled_plugins as $plugin_id) {
      $plugin = $this->ckeditorPluginManager->createInstance($plugin_id);
      $additional_libraries = array_diff($plugin->getLibraries($editor), $libraries);
      $libraries = array_merge($libraries, $additional_libraries);
    }

    return $libraries;
  }

  /**
   * Builds the "toolbar" configuration part of the CKEditor JS settings.
   *
   * @see getJSSettings()
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   An array containing the "toolbar" configuration.
   */
  public function buildToolbarJSSetting(Editor $editor) {
    $toolbar = [];

    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        $toolbar[] = $group;
      }
      $toolbar[] = '/';
    }
    return $toolbar;
  }

  /**
   * Builds the "contentsCss" configuration part of the CKEditor JS settings.
   *
   * @see getJSSettings()
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   A configured text editor object.
   * @return array
   *   An array containing the "contentsCss" configuration.
   */
  public function buildContentsCssJSSetting(Editor $editor) {
    $css = [
      drupal_get_path('module', 'ckeditor') . '/css/ckeditor-iframe.css',
      drupal_get_path('module', 'system') . '/css/components/align.module.css',
    ];
    $this->moduleHandler->alter('ckeditor_css', $css, $editor);
    // Get a list of all enabled plugins' iframe instance CSS files.
    $plugins_css = array_reduce($this->ckeditorPluginManager->getCssFiles($editor), function ($result, $item) {
      return array_merge($result, array_values($item));
    }, []);
    $css = array_merge($css, $plugins_css);
    $css = array_merge($css, _ckeditor_theme_css());
    $css = array_map('file_create_url', $css);
    $css = array_map('file_url_transform_relative', $css);

    return array_values($css);
  }

}
