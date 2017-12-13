<?php

namespace Drupal\Tests\ckeditor\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\KernelTests\KernelTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests different ways of enabling CKEditor plugins.
 *
 * @group ckeditor
 */
class CKEditorPluginManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'filter', 'editor', 'ckeditor'];

  /**
   * The manager for "CKEditor plugin" plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  protected function setUp() {
    parent::setUp();

    // Install the Filter module.

    // Create text format, associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();
    $editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
  }

  /**
   * Tests the enabling of plugins.
   */
  public function testEnabledPlugins() {
    $this->manager = $this->container->get('plugin.manager.ckeditor.plugin');
    $editor = Editor::load('filtered_html');

    // Case 1: no CKEditor plugins.
    $definitions = array_keys($this->manager->getDefinitions());
    sort($definitions);
    $this->assertIdentical(['drupalimage', 'drupalimagecaption', 'drupallink', 'internal', 'language', 'stylescombo'], $definitions, 'No CKEditor plugins found besides the built-in ones.');
    $enabled_plugins = [
      'drupalimage' => drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/plugin.js',
      'drupallink' => drupal_get_path('module', 'ckeditor') . '/js/plugins/drupallink/plugin.js',
    ];
    $this->assertIdentical($enabled_plugins, $this->manager->getEnabledPluginFiles($editor), 'Only built-in plugins are enabled.');
    $this->assertIdentical(['internal' => NULL] + $enabled_plugins, $this->manager->getEnabledPluginFiles($editor, TRUE), 'Only the "internal" plugin is enabled.');

    // Enable the CKEditor Test module, which has the Llama plugin (plus four
    // variations of it, to cover all possible ways a plugin can be enabled) and
    // clear the editor manager's cache so it is picked up.
    $this->enableModules(['ckeditor_test']);
    $this->manager = $this->container->get('plugin.manager.ckeditor.plugin');
    $this->manager->clearCachedDefinitions();

    // Case 2: CKEditor plugins are available.
    $plugin_ids = array_keys($this->manager->getDefinitions());
    sort($plugin_ids);
    $this->assertIdentical(['drupalimage', 'drupalimagecaption', 'drupallink', 'internal', 'language', 'llama', 'llama_button', 'llama_contextual', 'llama_contextual_and_button', 'llama_css', 'stylescombo'], $plugin_ids, 'Additional CKEditor plugins found.');
    $this->assertIdentical($enabled_plugins, $this->manager->getEnabledPluginFiles($editor), 'Only the internal plugins are enabled.');
    $this->assertIdentical(['internal' => NULL] + $enabled_plugins, $this->manager->getEnabledPluginFiles($editor, TRUE), 'Only the "internal" plugin is enabled.');

    // Case 3: enable each of the newly available plugins, if possible:
    // a. Llama: cannot be enabled, since it does not implement
    //    CKEditorPluginContextualInterface nor CKEditorPluginButtonsInterface.
    // b. LlamaContextual: enabled by adding the 'Strike' button, which is
    //    part of another plugin!
    // c. LlamaButton: automatically enabled by adding its 'Llama' button.
    // d. LlamaContextualAndButton: enabled by either b or c.
    // e. LlamaCSS: automatically enabled by add its 'LlamaCSS' button.
    // Below, we will first enable the "Llama" button, which will cause the
    // LlamaButton and LlamaContextualAndButton plugins to be enabled. Then we
    // will remove the "Llama" button and add the "Strike" button, which will
    // cause the LlamaContextual and LlamaContextualAndButton plugins to be
    // enabled. Then we will add the "Strike" button back again, which would
    // cause LlamaButton, LlamaContextual and LlamaContextualAndButton to be
    // enabled. Finally, we will add the "LlamaCSS" button which would cause
    // all four plugins to be enabled.
    $settings = $editor->getSettings();
    $original_toolbar = $settings['toolbar'];
    $settings['toolbar']['rows'][0][0]['items'][] = 'Llama';
    $editor->setSettings($settings);
    $editor->save();
    $file = [];
    $file['b'] = drupal_get_path('module', 'ckeditor_test') . '/js/llama_button.js';
    $file['c'] = drupal_get_path('module', 'ckeditor_test') . '/js/llama_contextual.js';
    $file['cb'] = drupal_get_path('module', 'ckeditor_test') . '/js/llama_contextual_and_button.js';
    $file['css'] = drupal_get_path('module', 'ckeditor_test') . '/js/llama_css.js';
    $expected = $enabled_plugins + ['llama_button' => $file['b'], 'llama_contextual_and_button' => $file['cb']];
    $this->assertIdentical($expected, $this->manager->getEnabledPluginFiles($editor), 'The LlamaButton and LlamaContextualAndButton plugins are enabled.');
    $this->assertIdentical(['internal' => NULL] + $expected, $this->manager->getEnabledPluginFiles($editor, TRUE), 'The LlamaButton and LlamaContextualAndButton plugins are enabled.');
    $settings['toolbar'] = $original_toolbar;
    $settings['toolbar']['rows'][0][0]['items'][] = 'Strike';
    $editor->setSettings($settings);
    $editor->save();
    $expected = $enabled_plugins + ['llama_contextual' => $file['c'], 'llama_contextual_and_button' => $file['cb']];
    $this->assertIdentical($expected, $this->manager->getEnabledPluginFiles($editor), 'The  LLamaContextual and LlamaContextualAndButton plugins are enabled.');
    $this->assertIdentical(['internal' => NULL] + $expected, $this->manager->getEnabledPluginFiles($editor, TRUE), 'The LlamaContextual and LlamaContextualAndButton plugins are enabled.');
    $settings['toolbar']['rows'][0][0]['items'][] = 'Llama';
    $editor->setSettings($settings);
    $editor->save();
    $expected = $enabled_plugins + ['llama_button' => $file['b'], 'llama_contextual' => $file['c'], 'llama_contextual_and_button' => $file['cb']];
    $this->assertIdentical($expected, $this->manager->getEnabledPluginFiles($editor), 'The LlamaButton, LlamaContextual and LlamaContextualAndButton plugins are enabled.');
    $this->assertIdentical(['internal' => NULL] + $expected, $this->manager->getEnabledPluginFiles($editor, TRUE), 'The LLamaButton, LlamaContextual and LlamaContextualAndButton plugins are enabled.');
    $settings['toolbar']['rows'][0][0]['items'][] = 'LlamaCSS';
    $editor->setSettings($settings);
    $editor->save();
    $expected = $enabled_plugins + ['llama_button' => $file['b'], 'llama_contextual' => $file['c'], 'llama_contextual_and_button' => $file['cb'], 'llama_css' => $file['css']];
    $this->assertIdentical($expected, $this->manager->getEnabledPluginFiles($editor), 'The LlamaButton, LlamaContextual, LlamaContextualAndButton and LlamaCSS plugins are enabled.');
    $this->assertIdentical(['internal' => NULL] + $expected, $this->manager->getEnabledPluginFiles($editor, TRUE), 'The LLamaButton, LlamaContextual, LlamaContextualAndButton and LlamaCSS plugins are enabled.');
  }

  /**
   * Tests the iframe instance CSS files of plugins.
   */
  public function testCssFiles() {
    $this->manager = $this->container->get('plugin.manager.ckeditor.plugin');
    $editor = Editor::load('filtered_html');

    // Case 1: no CKEditor iframe instance CSS file.
    $this->assertIdentical([], $this->manager->getCssFiles($editor), 'No iframe instance CSS file found.');

    // Enable the CKEditor Test module, which has the LlamaCss plugin and
    // clear the editor manager's cache so it is picked up.
    $this->enableModules(['ckeditor_test']);
    $this->manager = $this->container->get('plugin.manager.ckeditor.plugin');
    $settings = $editor->getSettings();
    // LlamaCss: automatically enabled by adding its 'LlamaCSS' button.
    $settings['toolbar']['rows'][0][0]['items'][] = 'LlamaCSS';
    $editor->setSettings($settings);
    $editor->save();

    // Case 2: CKEditor iframe instance CSS file.
    $expected = [
      'llama_css' => [drupal_get_path('module', 'ckeditor_test') . '/css/llama.css']
    ];
    $this->assertIdentical($expected, $this->manager->getCssFiles($editor), 'Iframe instance CSS file found.');
  }

}
