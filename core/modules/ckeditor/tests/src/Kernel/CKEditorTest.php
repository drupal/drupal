<?php

namespace Drupal\Tests\ckeditor\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests for the 'CKEditor' text editor plugin.
 *
 * @group ckeditor
 */
class CKEditorTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'editor',
    'ckeditor',
    'filter_test',
  ];

  /**
   * An instance of the "CKEditor" text editor plugin.
   *
   * @var \Drupal\ckeditor\Plugin\Editor\CKEditor
   */
  protected $ckeditor;

  /**
   * The Editor Plugin Manager.
   *
   * @var \Drupal\editor\Plugin\EditorManager;
   */
  protected $manager;

  protected function setUp(): void {
    parent::setUp();

    // Install the Filter module.

    // Create text format, associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<h2 id> <h3> <h4> <h5> <h6> <p> <br> <strong> <a href hreflang>',
          ],
        ],
      ],
    ]);
    $filtered_html_format->save();
    $editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ]);
    $editor->save();

    // Create "CKEditor" text editor plugin instance.
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
  }

  /**
   * Tests CKEditor::getJSSettings().
   */
  public function testGetJSSettings() {
    $editor = Editor::load('filtered_html');
    $query_string = '?0=';

    // Default toolbar.
    $expected_config = $this->getDefaultInternalConfig() + [
      'drupalImage_dialogTitleAdd' => 'Insert Image',
      'drupalImage_dialogTitleEdit' => 'Edit Image',
      'drupalLink_dialogTitleAdd' => 'Add Link',
      'drupalLink_dialogTitleEdit' => 'Edit Link',
      'allowedContent' => $this->getDefaultAllowedContentConfig(),
      'disallowedContent' => $this->getDefaultDisallowedContentConfig(),
      'toolbar' => $this->getDefaultToolbarConfig(),
      'contentsCss' => $this->getDefaultContentsCssConfig(),
      'extraPlugins' => 'drupalimage,drupallink',
      'language' => 'en',
      'stylesSet' => FALSE,
      'drupalExternalPlugins' => [
        'drupalimage' => file_url_transform_relative(file_create_url('core/modules/ckeditor/js/plugins/drupalimage/plugin.js')),
        'drupallink' => file_url_transform_relative(file_create_url('core/modules/ckeditor/js/plugins/drupallink/plugin.js')),
      ],
    ];
    $expected_config = $this->castSafeStrings($expected_config);
    ksort($expected_config);
    ksort($expected_config['allowedContent']);
    $this->assertIdentical($expected_config, $this->castSafeStrings($this->ckeditor->getJSSettings($editor)), 'Generated JS settings are correct for default configuration.');

    // Customize the configuration: add button, have two contextually enabled
    // buttons, and configure a CKEditor plugin setting.
    $this->enableModules(['ckeditor_test']);
    $this->container->get('plugin.manager.editor')->clearCachedDefinitions();
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $settings = $editor->getSettings();
    $settings['toolbar']['rows'][0][0]['items'][] = 'Strike';
    $settings['toolbar']['rows'][0][0]['items'][] = 'Format';
    $editor->setSettings($settings);
    $editor->save();
    $expected_config['toolbar'][0]['items'][] = 'Strike';
    $expected_config['toolbar'][0]['items'][] = 'Format';
    $expected_config['format_tags'] = 'p;h2;h3;h4;h5;h6';
    $expected_config['extraPlugins'] .= ',llama_contextual,llama_contextual_and_button';
    $expected_config['drupalExternalPlugins']['llama_contextual'] = file_url_transform_relative(file_create_url('core/modules/ckeditor/tests/modules/js/llama_contextual.js'));
    $expected_config['drupalExternalPlugins']['llama_contextual_and_button'] = file_url_transform_relative(file_create_url('core/modules/ckeditor/tests/modules/js/llama_contextual_and_button.js'));
    $expected_config['contentsCss'][] = file_url_transform_relative(file_create_url('core/modules/ckeditor/tests/modules/ckeditor_test.css')) . $query_string;
    ksort($expected_config);
    $this->assertIdentical($expected_config, $this->castSafeStrings($this->ckeditor->getJSSettings($editor)), 'Generated JS settings are correct for customized configuration.');

    // Change the allowed HTML tags; the "allowedContent" and "format_tags"
    // settings for CKEditor should automatically be updated as well.
    $format = $editor->getFilterFormat();
    $format->filters('filter_html')->settings['allowed_html'] .= '<pre class> <h1> <blockquote class="*"> <address class="foo bar-* *">';
    $format->save();

    $expected_config['allowedContent']['pre'] = ['attributes' => 'class', 'styles' => FALSE, 'classes' => TRUE];
    $expected_config['allowedContent']['h1'] = ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE];
    $expected_config['allowedContent']['blockquote'] = ['attributes' => 'class', 'styles' => FALSE, 'classes' => TRUE];
    $expected_config['allowedContent']['address'] = ['attributes' => 'class', 'styles' => FALSE, 'classes' => 'foo,bar-*'];
    $expected_config['format_tags'] = 'p;h1;h2;h3;h4;h5;h6;pre';
    ksort($expected_config['allowedContent']);
    $this->assertIdentical($expected_config, $this->castSafeStrings($this->ckeditor->getJSSettings($editor)), 'Generated JS settings are correct for customized configuration.');

    // Disable the filter_html filter: allow *all *tags.
    $format->setFilterConfig('filter_html', ['status' => 0]);
    $format->save();

    $expected_config['allowedContent'] = TRUE;
    $expected_config['disallowedContent'] = FALSE;
    $expected_config['format_tags'] = 'p;h1;h2;h3;h4;h5;h6;pre';
    $this->assertIdentical($expected_config, $this->castSafeStrings($this->ckeditor->getJSSettings($editor)), 'Generated JS settings are correct for customized configuration.');

    // Enable the filter_test_restrict_tags_and_attributes filter.
    $format->setFilterConfig('filter_test_restrict_tags_and_attributes', [
      'status' => 1,
      'settings' => [
        'restrictions' => [
          'allowed' => [
            'p' => TRUE,
            'a' => [
              'href' => TRUE,
              'rel' => ['nofollow' => TRUE],
              'class' => ['external' => TRUE],
              'target' => ['_blank' => FALSE],
            ],
            'span' => [
              'class' => ['dodo' => FALSE],
              'property' => ['dc:*' => TRUE],
              'rel' => ['foaf:*' => FALSE],
              'style' => ['underline' => FALSE, 'color' => FALSE, 'font-size' => TRUE],
            ],
            '*' => [
              'style' => FALSE,
              'on*' => FALSE,
              'class' => ['is-a-hipster-llama' => TRUE, 'and-more' => TRUE],
              'data-*' => TRUE,
            ],
            'del' => FALSE,
          ],
        ],
      ],
    ]);
    $format->save();

    $expected_config['allowedContent'] = [
      'p' => [
        'attributes' => TRUE,
        'styles' => FALSE,
        'classes' => 'is-a-hipster-llama,and-more',
      ],
      'a' => [
        'attributes' => 'href,rel,class,target',
        'styles' => FALSE,
        'classes' => 'external',
      ],
      'span' => [
        'attributes' => 'class,property,rel,style',
        'styles' => 'font-size',
        'classes' => FALSE,
      ],
      '*' => [
        'attributes' => 'class,data-*',
        'styles' => FALSE,
        'classes' => 'is-a-hipster-llama,and-more',
      ],
      'del' => [
        'attributes' => FALSE,
        'styles' => FALSE,
        'classes' => FALSE,
      ],
    ];
    $expected_config['disallowedContent'] = [
      'span' => [
        'styles' => 'underline,color',
        'classes' => 'dodo',
      ],
      '*' => [
        'attributes' => 'on*',
      ],
    ];
    $expected_config['format_tags'] = 'p';
    ksort($expected_config);
    ksort($expected_config['allowedContent']);
    ksort($expected_config['disallowedContent']);
    $this->assertIdentical($expected_config, $this->castSafeStrings($this->ckeditor->getJSSettings($editor)), 'Generated JS settings are correct for customized configuration.');
  }

  /**
   * Tests CKEditor::buildToolbarJSSetting().
   */
  public function testBuildToolbarJSSetting() {
    $editor = Editor::load('filtered_html');

    // Default toolbar.
    $expected = $this->getDefaultToolbarConfig();
    $this->assertIdentical($expected, $this->castSafeStrings($this->ckeditor->buildToolbarJSSetting($editor)), '"toolbar" configuration part of JS settings built correctly for default toolbar.');

    // Customize the configuration.
    $settings = $editor->getSettings();
    $settings['toolbar']['rows'][0][0]['items'][] = 'Strike';
    $editor->setSettings($settings);
    $editor->save();
    $expected[0]['items'][] = 'Strike';
    $this->assertIdentical($expected, $this->castSafeStrings($this->ckeditor->buildToolbarJSSetting($editor)), '"toolbar" configuration part of JS settings built correctly for customized toolbar.');

    // Enable the editor_test module, customize further.
    $this->enableModules(['ckeditor_test']);
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    // Override the label of a toolbar component.
    $settings['toolbar']['rows'][0][0]['name'] = 'JunkScience';
    $settings['toolbar']['rows'][0][0]['items'][] = 'Llama';
    $editor->setSettings($settings);
    $editor->save();
    $expected[0]['name'] = 'JunkScience';
    $expected[0]['items'][] = 'Llama';
    $this->assertIdentical($expected, $this->castSafeStrings($this->ckeditor->buildToolbarJSSetting($editor)), '"toolbar" configuration part of JS settings built correctly for customized toolbar with contrib module-provided CKEditor plugin.');
  }

  /**
   * Tests CKEditor::buildContentsCssJSSetting().
   */
  public function testBuildContentsCssJSSetting() {
    $editor = Editor::load('filtered_html');
    $query_string = '?0=';

    // Default toolbar.
    $expected = $this->getDefaultContentsCssConfig();
    $this->assertIdentical($expected, $this->ckeditor->buildContentsCssJSSetting($editor), '"contentsCss" configuration part of JS settings built correctly for default toolbar.');

    // Enable the editor_test module, which implements hook_ckeditor_css_alter().
    $this->enableModules(['ckeditor_test']);
    $expected[] = file_url_transform_relative(file_create_url(drupal_get_path('module', 'ckeditor_test') . '/ckeditor_test.css')) . $query_string;
    $this->assertIdentical($expected, $this->ckeditor->buildContentsCssJSSetting($editor), '"contentsCss" configuration part of JS settings built correctly while a hook_ckeditor_css_alter() implementation exists.');

    // Enable LlamaCss plugin, which adds an additional CKEditor stylesheet.
    $this->container->get('plugin.manager.editor')->clearCachedDefinitions();
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $settings = $editor->getSettings();
    // LlamaCss: automatically enabled by adding its 'LlamaCSS' button.
    $settings['toolbar']['rows'][0][0]['items'][] = 'LlamaCSS';
    $editor->setSettings($settings);
    $editor->save();
    $expected[] = file_url_transform_relative(file_create_url(drupal_get_path('module', 'ckeditor_test') . '/css/llama.css')) . $query_string;
    $this->assertIdentical($expected, $this->ckeditor->buildContentsCssJSSetting($editor), '"contentsCss" configuration part of JS settings built correctly while a CKEditorPluginInterface implementation exists.');

    // Enable the Bartik theme, which specifies a CKEditor stylesheet.
    \Drupal::service('theme_installer')->install(['bartik']);
    $this->config('system.theme')->set('default', 'bartik')->save();
    $expected[] = file_url_transform_relative(file_create_url('core/themes/bartik/css/base/elements.css')) . $query_string;
    $expected[] = file_url_transform_relative(file_create_url('core/themes/bartik/css/components/captions.css')) . $query_string;
    $expected[] = file_url_transform_relative(file_create_url('core/themes/bartik/css/components/table.css')) . $query_string;
    $expected[] = file_url_transform_relative(file_create_url('core/themes/bartik/css/components/text-formatted.css')) . $query_string;
    $expected[] = file_url_transform_relative(file_create_url('core/themes/bartik/css/classy/components/media-embed-error.css')) . $query_string;
    $this->assertIdentical($expected, $this->ckeditor->buildContentsCssJSSetting($editor), '"contentsCss" configuration part of JS settings built correctly while a theme providing a CKEditor stylesheet exists.');
  }

  /**
   * Tests Internal::getConfig().
   */
  public function testInternalGetConfig() {
    $editor = Editor::load('filtered_html');
    $internal_plugin = $this->container->get('plugin.manager.ckeditor.plugin')->createInstance('internal');

    // Default toolbar.
    $expected = $this->getDefaultInternalConfig();
    $expected['disallowedContent'] = $this->getDefaultDisallowedContentConfig();
    $expected['allowedContent'] = $this->getDefaultAllowedContentConfig();
    $this->assertEqual($expected, $internal_plugin->getConfig($editor), '"Internal" plugin configuration built correctly for default toolbar.');

    // Format dropdown/button enabled: new setting should be present.
    $settings = $editor->getSettings();
    $settings['toolbar']['rows'][0][0]['items'][] = 'Format';
    $editor->setSettings($settings);
    $expected['format_tags'] = 'p;h2;h3;h4;h5;h6';
    $this->assertEqual($expected, $internal_plugin->getConfig($editor), '"Internal" plugin configuration built correctly for customized toolbar.');
  }

  /**
   * Tests StylesCombo::getConfig().
   */
  public function testStylesComboGetConfig() {
    $editor = Editor::load('filtered_html');
    $stylescombo_plugin = $this->container->get('plugin.manager.ckeditor.plugin')->createInstance('stylescombo');

    // Styles dropdown/button enabled: new setting should be present.
    $settings = $editor->getSettings();
    $settings['toolbar']['rows'][0][0]['items'][] = 'Styles';
    $settings['plugins']['stylescombo']['styles'] = '';
    $editor->setSettings($settings);
    $editor->save();
    $expected['stylesSet'] = [];
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Configure the optional "styles" setting in odd ways that shouldn't affect
    // the end result.
    $settings['plugins']['stylescombo']['styles'] = "   \n";
    $editor->setSettings($settings);
    $editor->save();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor));
    $settings['plugins']['stylescombo']['styles'] = "\r\n  \n  \r  \n ";
    $editor->setSettings($settings);
    $editor->save();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Now configure it properly, the end result should change.
    $settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.mAgical.Callout|Callout";
    $editor->setSettings($settings);
    $editor->save();
    $expected['stylesSet'] = [
      ['name' => 'Title', 'element' => 'h1', 'attributes' => ['class' => 'title']],
      ['name' => 'Callout', 'element' => 'p', 'attributes' => ['class' => 'mAgical Callout']],
    ];
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Same configuration, but now interspersed with nonsense. Should yield the
    // same result.
    $settings['plugins']['stylescombo']['styles'] = "  h1 .title   |  Title \r \n\r  \np.mAgical  .Callout|Callout\r";
    $editor->setSettings($settings);
    $editor->save();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Slightly different configuration: class names are optional.
    $settings['plugins']['stylescombo']['styles'] = "      h1 |  Title ";
    $editor->setSettings($settings);
    $editor->save();
    $expected['stylesSet'] = [['name' => 'Title', 'element' => 'h1']];
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Invalid syntax should cause stylesSet to be set to FALSE.
    $settings['plugins']['stylescombo']['styles'] = "h1";
    $editor->setSettings($settings);
    $editor->save();
    $expected['stylesSet'] = FALSE;
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');
  }

  /**
   * Tests language list availability in CKEditor.
   */
  public function testLanguages() {
    // Get CKEditor supported language codes and spot-check.
    $this->enableModules(['language']);
    $this->installConfig(['language']);
    $langcodes = $this->ckeditor->getLangcodes();

    // Language codes transformed with browser mappings.
    $this->assertTrue($langcodes['pt-pt'] == 'pt', '"pt" properly resolved');
    $this->assertTrue($langcodes['zh-hans'] == 'zh-cn', '"zh-hans" properly resolved');

    // Language code both in Drupal and CKEditor.
    $this->assertTrue($langcodes['gl'] == 'gl', '"gl" properly resolved');

    // Language codes only in CKEditor.
    $this->assertTrue($langcodes['en-au'] == 'en-au', '"en-au" properly resolved');
    $this->assertTrue($langcodes['sr-latn'] == 'sr-latn', '"sr-latn" properly resolved');

    // No locale module, so even though languages are enabled, CKEditor should
    // still be in English.
    $this->assertCKEditorLanguage('en');
  }

  /**
   * Tests that CKEditor plugins participate in JS translation.
   */
  public function testJSTranslation() {
    $this->enableModules(['language', 'locale']);
    $this->installSchema('locale', 'locales_source');
    $this->installSchema('locale', 'locales_location');
    $this->installSchema('locale', 'locales_target');
    $editor = Editor::load('filtered_html');
    $this->ckeditor->getJSSettings($editor);
    $localeStorage = $this->container->get('locale.storage');
    $string = $localeStorage->findString(['source' => 'Edit Link', 'context' => '']);
    $this->assertTrue(!empty($string), 'String from JavaScript file saved.');

    // With locale module, CKEditor should not adhere to the language selected.
    $this->assertCKEditorLanguage();
  }

  /**
   * Assert that CKEditor picks the expected language when French is default.
   *
   * @param string $langcode
   *   Language code to assert for. Defaults to French. That is the default
   *   language set in this assertion.
   */
  protected function assertCKEditorLanguage($langcode = 'fr') {
    // Set French as the site default language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    // Reset the language manager so new negotiations attempts will fall back on
    // French. Reinject the language manager CKEditor to use the current one.
    $this->container->get('language_manager')->reset();
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');

    // Test that we now get the expected language.
    $editor = Editor::load('filtered_html');
    $settings = $this->ckeditor->getJSSettings($editor);
    $this->assertEqual($settings['language'], $langcode);
  }

  protected function getDefaultInternalConfig() {
    return [
      'customConfig' => '',
      'pasteFromWordPromptCleanup' => TRUE,
      'resize_dir' => 'vertical',
      'justifyClasses' => ['text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify'],
      'entities' => FALSE,
      'disableNativeSpellChecker' => FALSE,
    ];
  }

  protected function getDefaultAllowedContentConfig() {
    return [
      'h2' => ['attributes' => 'id', 'styles' => FALSE, 'classes' => FALSE],
      'h3' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'h4' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'h5' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'h6' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'p' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'br' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'strong' => ['attributes' => FALSE, 'styles' => FALSE, 'classes' => FALSE],
      'a' => ['attributes' => 'href,hreflang', 'styles' => FALSE, 'classes' => FALSE],
      '*' => ['attributes' => 'lang,dir', 'styles' => FALSE, 'classes' => FALSE],
    ];
  }

  protected function getDefaultDisallowedContentConfig() {
    return [
      '*' => ['attributes' => 'on*'],
    ];
  }

  protected function getDefaultToolbarConfig() {
    return [
      [
        'name' => 'Formatting',
        'items' => ['Bold', 'Italic'],
      ],
      [
        'name' => 'Links',
        'items' => ['DrupalLink', 'DrupalUnlink'],
      ],
      [
        'name' => 'Lists',
        'items' => ['BulletedList', 'NumberedList'],
      ],
      [
        'name' => 'Media',
        'items' => ['Blockquote', 'DrupalImage'],
      ],
      [
        'name' => 'Tools',
        'items' => ['Source'],
      ],
      '/',
    ];
  }

  protected function getDefaultContentsCssConfig() {
    $query_string = '?0=';
    return [
      file_url_transform_relative(file_create_url('core/modules/ckeditor/css/ckeditor-iframe.css')) . $query_string,
      file_url_transform_relative(file_create_url('core/modules/system/css/components/align.module.css')) . $query_string,
    ];
  }

}
