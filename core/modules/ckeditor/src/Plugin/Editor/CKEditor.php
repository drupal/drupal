<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Plugin\Editor\CKEditor.
 */

namespace Drupal\ckeditor\Plugin\Editor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor as EditorEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a CKEditor-based text editor for Drupal.
 *
 * @Editor(
 *   id = "ckeditor",
 *   label = @Translation("CKEditor"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE
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
   * Constructs a Drupal\Component\Plugin\PluginBase object.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CKEditorPluginManager $ckeditor_plugin_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ckeditorPluginManager = $ckeditor_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
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
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return array(
      'toolbar' => array(
        'rows' => array(
          // Button groups.
          array(
            array(
              'name' => t('Formatting'),
              'items' => array('Bold', 'Italic',),
            ),
            array(
              'name' => t('Links'),
              'items' => array('DrupalLink', 'DrupalUnlink',),
            ),
            array(
              'name' => t('Lists'),
              'items' => array('BulletedList', 'NumberedList',),
            ),
            array(
              'name' => t('Media'),
              'items' => array('Blockquote', 'DrupalImage',),
            ),
            array(
              'name' => t('Tools'),
              'items' => array('Source',),
            ),
          ),
        ),
      ),
      'plugins' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, EditorEntity $editor) {
    $settings = $editor->getSettings();

    $ckeditor_settings_toolbar = array(
      '#theme' => 'ckeditor_settings_toolbar',
      '#editor' => $editor,
      '#plugins' => $this->ckeditorPluginManager->getButtons(),
    );
    $form['toolbar'] = array(
      '#type' => 'container',
      '#attached' => array(
        'library' => array('ckeditor/drupal.ckeditor.admin'),
        'js' => array(
          array(
            'type' => 'setting',
            'data' => array('ckeditor' => array(
              'toolbarAdmin' => drupal_render($ckeditor_settings_toolbar),
            )),
          )
        ),
      ),
      '#attributes' => array('class' => array('ckeditor-toolbar-configuration')),
    );

    $form['toolbar']['button_groups'] = array(
      '#type' => 'textarea',
      '#title' => t('Toolbar buttons'),
      '#default_value' => json_encode($settings['toolbar']['rows']),
      '#attributes' => array('class' => array('ckeditor-toolbar-textarea')),
    );

    // CKEditor plugin settings, if any.
    $form['plugin_settings'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('CKEditor plugin settings'),
      '#attributes' => array(
        'id' => 'ckeditor-plugin-settings',
      ),
    );
    $this->ckeditorPluginManager->injectPluginSettingsForm($form, $form_state, $editor);
    if (count(Element::children($form['plugins'])) === 0) {
      unset($form['plugins']);
      unset($form['plugin_settings']);
    }

    // Hidden CKEditor instance. We need a hidden CKEditor instance with all
    // plugins enabled, so we can retrieve CKEditor's per-feature metadata (on
    // which tags, attributes, styles and classes are enabled). This metadata is
    // necessary for certain filters' (e.g. the html_filter filter) settings to
    // be updated accordingly.
    // Get a list of all external plugins and their corresponding files.
    $plugins = array_keys($this->ckeditorPluginManager->getDefinitions());
    $all_external_plugins = array();
    foreach ($plugins as $plugin_id) {
      $plugin = $this->ckeditorPluginManager->createInstance($plugin_id);
      if (!$plugin->isInternal()) {
        $all_external_plugins[$plugin_id] = $plugin->getFile();
      }
    }
    // Get a list of all buttons that are provided by all plugins.
    $all_buttons = array_reduce($this->ckeditorPluginManager->getButtons(), function($result, $item) {
      return array_merge($result, array_keys($item));
    }, array());
    // Build a fake Editor object, which we'll use to generate JavaScript
    // settings for this fake Editor instance.
    $fake_editor = entity_create('editor', array(
      'format' => $editor->id(),
      'editor' => 'ckeditor',
      'settings' => array(
        // Single toolbar row, single button group, all existing buttons.
        'toolbar' => array(
         'rows' => array(
           0 => array(
             0 => array(
               'name' => 'All existing buttons',
               'items' => $all_buttons,
             )
           )
         ),
        ),
        'plugins' => $settings['plugins'],
      ),
    ));
    $config = $this->getJSSettings($fake_editor);
    // Remove the ACF configuration that is generated based on filter settings,
    // because otherwise we cannot retrieve per-feature metadata.
    unset($config['allowedContent']);
    $form['hidden_ckeditor'] = array(
      '#markup' => '<div id="ckeditor-hidden" class="hidden"></div>',
      '#attached' => array(
        'js' => array(
          array(
            'type' => 'setting',
            'data' => array('ckeditor' => array(
              'hiddenCKEditorConfig' => $config,
            )),
          ),
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit(array $form, FormStateInterface $form_state) {
    // Modify the toolbar settings by reference. The values in
    // $form_state->getValue(array('editor', 'settings')) will be saved directly
    // by editor_form_filter_admin_format_submit().
    $toolbar_settings = &$form_state->getValue(array('editor', 'settings', 'toolbar'));

    // The rows key is not built into the form structure, so decode the button
    // groups data into this new key and remove the button_groups key.
    $toolbar_settings['rows'] = json_decode($toolbar_settings['button_groups'], TRUE);
    unset($toolbar_settings['button_groups']);

    // Remove the plugin settings' vertical tabs state; no need to save that.
    if ($form_state->hasValue(array('editor', 'settings', 'plugins'))) {
      $form_state->unsetValue(array('editor', 'settings', 'plugin_settings'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(EditorEntity $editor) {
    $settings = array();

    // Get the settings for all enabled plugins, even the internal ones.
    $enabled_plugins = array_keys($this->ckeditorPluginManager->getEnabledPluginFiles($editor, TRUE));
    foreach ($enabled_plugins as $plugin_id) {
      $plugin = $this->ckeditorPluginManager->createInstance($plugin_id);
      $settings += $plugin->getConfig($editor);
    }

    // Fall back on English if no matching language code was found.
    $display_langcode = 'en';

    // Map the interface language code to a CKEditor translation.
    $ckeditor_langcodes = $this->getLangcodes();
    $language_interface = $this->languageManager->getCurrentLanguage();
    if (isset($ckeditor_langcodes[$language_interface->getId()])) {
      $display_langcode = $ckeditor_langcodes[$language_interface->getId()];
    }

    // Next, set the most fundamental CKEditor settings.
    $external_plugin_files = $this->ckeditorPluginManager->getEnabledPluginFiles($editor);
    $settings += array(
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
    );

    // Finally, set Drupal-specific CKEditor settings.
    $settings += array(
      'drupalExternalPlugins' => array_map('file_create_url', $external_plugin_files),
    );

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
      $langcodes = array();
      // Collect languages included with CKEditor based on file listing.
      $ckeditor_languages = new \GlobIterator(DRUPAL_ROOT . '/core/assets/vendor/ckeditor/lang/*.js');
      foreach ($ckeditor_languages as $language_file) {
        $langcode = $language_file->getBasename('.js');
        $langcodes[$langcode] = $langcode;
      }
      \Drupal::cache()->set('ckeditor.langcodes', $langcodes);
    }

    // Get language mapping if available to map to Drupal language codes.
    // This is configurable in the user interface and not expensive to get, so
    // we don't include it in the cached language list.
    $language_mappings = $this->moduleHandler->moduleExists('language') ? language_get_browser_drupal_langcode_mappings() : array();
    foreach ($langcodes as $langcode) {
      // If this language code is available in a Drupal mapping, use that to
      // compute a possibility for matching from the Drupal langcode to the
      // CKEditor langcode.
      // e.g. CKEditor uses the langcode 'no' for Norwegian, Drupal uses 'nb'.
      // This would then remove the 'no' => 'no' mapping and replace it with
      // 'nb' => 'no'. Now Drupal knows which CKEditor translation to load.
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
  public function getLibraries(EditorEntity $editor) {
    $libraries = array(
      'ckeditor/drupal.ckeditor',
    );

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
  public function buildToolbarJSSetting(EditorEntity $editor) {
    $toolbar = array();

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
  public function buildContentsCssJSSetting(EditorEntity $editor) {
    $css = array(
      drupal_get_path('module', 'ckeditor') . '/css/ckeditor-iframe.css',
      drupal_get_path('module', 'system') . '/css/system.module.css',
    );
    $this->moduleHandler->alter('ckeditor_css', $css, $editor);
    $css = array_merge($css, _ckeditor_theme_css());
    $css = array_map('file_create_url', $css);

    return array_values($css);
  }

}
