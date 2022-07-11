<?php

namespace Drupal\ckeditor\Plugin\Editor;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
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
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module list service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CKEditorPluginManager $ckeditor_plugin_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, RendererInterface $renderer, StateInterface $state, FileUrlGeneratorInterface $file_url_generator = NULL, ModuleExtensionList $module_list = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ckeditorPluginManager = $ckeditor_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    $this->state = $state;
    if (!$file_url_generator) {
      @trigger_error('Calling CKEditor::__construct() without the $file_url_generator argument is deprecated in drupal:9.3.0 and will be required before drupal:10.0.0. See https://www.drupal.org/node/2940031', E_USER_DEPRECATED);
      $file_url_generator = \Drupal::service('file_url_generator');
    }
    $this->fileUrlGenerator = $file_url_generator;
    if (!$module_list) {
      @trigger_error('Calling CKEditor::__construct() without the $module_list argument is deprecated in drupal:9.3.0 and is required in drupal:10.0.0. See https://www.drupal.org/node/2940438', E_USER_DEPRECATED);
      $module_list = \Drupal::service('extension.list.module');
    }
    $this->moduleList = $module_list;
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
      $container->get('renderer'),
      $container->get('state'),
      $container->get('file_url_generator'),
      $container->get('extension.list.module')
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
      'plugins' => [],
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
    // The rows key is not built into the form structure, so decode the button
    // groups data into this new key and remove the button_groups key.
    $form_state->setValue(['toolbar', 'rows'], json_decode($form_state->getValue(['toolbar', 'button_groups']), TRUE));
    $form_state->unsetValue(['toolbar', 'button_groups']);

    // Remove the plugin settings' vertical tabs state; no need to save that.
    if ($form_state->hasValue('plugins')) {
      $form_state->unsetValue('plugin_settings');
    }

    // Ensure plugin settings are only saved for plugins that are actually
    // enabled.
    $about_to_be_saved_editor = Editor::create([
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => $form_state->getValue('toolbar'),
        'plugins' => $form_state->getValue('plugins'),
      ],
    ]);
    $enabled_plugins = _ckeditor_get_enabled_plugins($about_to_be_saved_editor);
    $plugin_settings = $form_state->getValue('plugins', []);
    foreach (array_keys($plugin_settings) as $plugin_id) {
      if (!in_array($plugin_id, $enabled_plugins, TRUE)) {
        unset($plugin_settings[$plugin_id]);
      }
    }
    $form_state->setValue('plugins', $plugin_settings);
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
    $settings += [
      'drupalExternalPlugins' => array_map([$this->fileUrlGenerator, 'generateString'], $external_plugin_files),
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
   *
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
   *
   * @return array
   *   An array containing the "contentsCss" configuration.
   */
  public function buildContentsCssJSSetting(Editor $editor) {
    $css = [
      $this->moduleList->getPath('ckeditor') . '/css/ckeditor-iframe.css',
      $this->moduleList->getPath('system') . '/css/components/align.module.css',
    ];
    $this->moduleHandler->alter('ckeditor_css', $css, $editor);
    // Get a list of all enabled plugins' iframe instance CSS files.
    $plugins_css = array_reduce($this->ckeditorPluginManager->getCssFiles($editor), function ($result, $item) {
      return array_merge($result, array_values($item));
    }, []);
    $css = array_merge($css, $plugins_css);
    $css = array_merge($css, _ckeditor_theme_css());
    $query_string = $this->state->get('system.css_js_query_string', '0');
    $css = array_map(function ($item) use ($query_string) {
      $query_string_separator = (strpos($item, '?') !== FALSE) ? '&' : '?';
      return $item . $query_string_separator . $query_string;
    }, $css);
    $css = array_map([$this->fileUrlGenerator, 'generateString'], $css);

    return array_values($css);
  }

}
