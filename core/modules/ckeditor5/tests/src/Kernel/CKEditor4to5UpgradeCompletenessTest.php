<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\NestedArray;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\ckeditor5\Plugin\CKEditor4To5Upgrade\Core
 * @group ckeditor5
 * @internal
 */
class CKEditor4to5UpgradeCompletenessTest extends KernelTestBase {

  /**
   * The "CKEditor 4 plugin" plugin manager.
   *
   * @var \Drupal\ckeditor\CKEditorPluginManager
   */
  protected $cke4PluginManager;

  /**
   * The "CKEditor 5 plugin" plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
   */
  protected $cke5PluginManager;

  /**
   * The CKEditor 4 to 5 upgrade plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginManager
   */
  protected $upgradePluginManager;

  /**
   * Smart default settings utility.
   *
   * @var \Drupal\ckeditor5\SmartDefaultSettings
   */
  protected $smartDefaultSettings;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'ckeditor5',
    // Enabled because of ::testCKEditor5ConfigurableSubsetPlugins().
    'filter',
    // Enabled because of \Drupal\media\Plugin\CKEditorPlugin\DrupalMedia.
    'media',
    // Enabled because of \Drupal\media_library\Plugin\CKEditorPlugin\DrupalMediaLibrary.
    'media_library',
    // Enabled for media_library.
    'views',
    // These modules must be installed for ckeditor5_config_schema_info_alter()
    // to work, which in turn is necessary for the plugin definition validation
    // logic.
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::validateDrupalAspects()
    'filter',
    'editor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The tested service is private; expose it under a public test-only alias.
    $this->container->setAlias('sut', 'plugin.manager.ckeditor4to5upgrade.plugin');

    $this->cke4PluginManager = $this->container->get('plugin.manager.ckeditor.plugin');
    $this->cke5PluginManager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->upgradePluginManager = $this->container->get('sut');
    $this->smartDefaultSettings = $this->container->get('ckeditor5.smart_default_settings');
  }

  /**
   * Tests that all CKEditor 4 buttons in core have an upgrade path.
   */
  public function testButtons(): void {
    $cke4_buttons = array_keys(NestedArray::mergeDeepArray($this->cke4PluginManager->getButtons()));

    foreach ($cke4_buttons as $button) {
      $equivalent = $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem($button, HTMLRestrictions::emptySet());
      $this->assertTrue($equivalent === NULL || (is_array($equivalent) && Inspector::assertAllStrings($equivalent)));
      // The returned equivalent CKEditor 5 toolbar item(s) must exist.
      if (is_string($equivalent)) {
        foreach (explode(',', $equivalent) as $equivalent_cke5_toolbar_item) {
          $this->assertArrayHasKey($equivalent_cke5_toolbar_item, $this->cke5PluginManager->getToolbarItems());
        }
      }
    }
  }

  /**
   * Tests that the test-only CKEditor 4 module does not have an upgrade path.
   */
  public function testButtonsWithTestOnlyModule(): void {
    $this->enableModules(['ckeditor_test']);
    $this->cke4PluginManager = $this->container->get('plugin.manager.ckeditor.plugin');

    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('No upgrade path found for the "LlamaCSS" button.');

    $this->testButtons();
  }

  /**
   * Tests that all configurable CKEditor 4 plugins in core have an upgrade path.
   */
  public function testSettings(): void {
    $cke4_configurable_plugins = [];
    foreach ($this->cke4PluginManager->getDefinitions() as $plugin_id => $definition) {
      // Special case: DrupalImage.
      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalImage
      // @see \Drupal\editor\Entity\Editor::getImageUploadSettings()
      if ($plugin_id === 'drupalimage') {
        continue;
      }

      if (is_subclass_of($definition['class'], CKEditorPluginConfigurableInterface::class)) {
        $cke4_configurable_plugins[] = $plugin_id;
      }
    }

    foreach ($cke4_configurable_plugins as $plugin_id) {
      $cke5_plugin_settings = $this->upgradePluginManager->mapCKEditor4SettingsToCKEditor5Configuration($plugin_id, []);
      $this->assertTrue($cke5_plugin_settings === NULL || is_array($cke5_plugin_settings));
      // The returned equivalent CKEditor 5 plugin must exist.
      if (is_array($cke5_plugin_settings)) {
        $cke5_plugin_id = array_keys($cke5_plugin_settings)[0];
        $this->assertArrayHasKey($cke5_plugin_id, $this->cke5PluginManager->getDefinitions());
      }
    }
  }

  /**
   * Tests that the test-only CKEditor 4 module does not have an upgrade path.
   */
  public function testSettingsWithTestOnlyModule(): void {
    $this->enableModules(['ckeditor_test']);
    $this->cke4PluginManager = $this->container->get('plugin.manager.ckeditor.plugin');

    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('No upgrade path found for the "llama_contextual_and_button" plugin settings.');

    $this->testSettings();
  }

  /**
   * Tests that all elements subset plugins in core have an upgrade path.
   */
  public function testCKEditor5ConfigurableSubsetPlugins(): void {
    $cke5_elements_subset_plugins = [];
    foreach ($this->cke5PluginManager->getDefinitions() as $plugin_id => $definition) {
      // Special case: SourceEditing.
      // @see \Drupal\ckeditor5\SmartDefaultSettings::computeSubsetSettingForEnabledPluginsWithSubsets()
      if ($plugin_id === 'ckeditor5_sourceEditing') {
        continue;
      }

      if (is_a($definition->getClass(), CKEditor5PluginElementsSubsetInterface::class, TRUE)) {
        $cke5_elements_subset_plugins[] = $plugin_id;
      }
    }

    foreach ($cke5_elements_subset_plugins as $plugin_id) {
      $cke5_plugin_configuration = $this->upgradePluginManager->computeCKEditor5PluginSubsetConfiguration($plugin_id, FilterFormat::create());
      $this->assertTrue($cke5_plugin_configuration === NULL || is_array($cke5_plugin_configuration));
    }
  }

  /**
   * Tests that only one plugin can provide an upgrade path for a button.
   */
  public function testOnlyOneUpgradePluginAllowedPerCKEditor4Button(): void {
    $this->enableModules(['ckeditor4to5upgrade_plugin_test']);
    \Drupal::state()->set('ckeditor4to5upgrade_plugin_test', 'duplicate_button');

    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('The "DrupalImage" CKEditor 4 button is already being upgraded by the "core" CKEditor4To5Upgrade plugin, the "foo" plugin is as well. This conflict needs to be resolved.');

    $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem('foo', HTMLRestrictions::emptySet());
  }

  /**
   * Tests detecting a lying upgrade plugin cke4_button annotation.
   */
  public function testLyingUpgradePluginForCKEditor4Button(): void {
    $this->enableModules(['ckeditor4to5upgrade_plugin_test']);
    \Drupal::state()->set('ckeditor4to5upgrade_plugin_test', 'lying_button');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The "foo" CKEditor4To5Upgrade plugin claims to provide an upgrade path for the "foo" CKEditor 4 button but does not.');

    $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem('foo', HTMLRestrictions::emptySet());
  }

  /**
   * Tests that only one plugin can provide an upgrade path for plugin settings.
   */
  public function testOnlyOneUpgradePluginAllowedPerCKEditor4PluginSettings(): void {
    $this->enableModules(['ckeditor4to5upgrade_plugin_test']);
    \Drupal::state()->set('ckeditor4to5upgrade_plugin_test', 'duplicate_plugin_settings');

    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('The "stylescombo" CKEditor 4 plugin\'s settings are already being upgraded by the "core" CKEditor4To5Upgrade plugin, the "foo" plugin is as well. This conflict needs to be resolved.');

    $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem('foo', HTMLRestrictions::emptySet());
  }

  /**
   * Tests detecting a lying upgrade plugin cke4_plugin_settings annotation.
   */
  public function testLyingUpgradePluginForCKEditor4PluginSettings(): void {
    $this->enableModules(['ckeditor4to5upgrade_plugin_test']);
    \Drupal::state()->set('ckeditor4to5upgrade_plugin_test', 'lying_plugin_settings');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The "foo" CKEditor4To5Upgrade plugin claims to provide an upgrade path for the "foo" CKEditor 4 plugin settings but does not.');

    $this->upgradePluginManager->mapCKEditor4SettingsToCKEditor5Configuration('foo', []);
  }

  /**
   * Tests that only one plugin can provide an upgrade path for a subset plugin.
   */
  public function testOnlyOneUpgradePluginAllowedPerCKEditor5ConfigurableSubsetPlugin(): void {
    $this->enableModules(['ckeditor4to5upgrade_plugin_test']);
    \Drupal::state()->set('ckeditor4to5upgrade_plugin_test', 'duplicate_subset');

    $this->expectException(\OutOfBoundsException::class);
    $this->expectExceptionMessage('The "ckeditor5_heading" CKEditor 5 plugin\'s elements subset configuration is already being computed by the "core" CKEditor4To5Upgrade plugin, the "foo" plugin is as well. This conflict needs to be resolved.');

    $this->upgradePluginManager->computeCKEditor5PluginSubsetConfiguration('foo', FilterFormat::create());
  }

  /**
   * Tests detecting lying cke5_plugin_elements_subset_configuration annotation.
   */
  public function testLyingUpgradePluginForCKEditor5ConfigurableSubsetPlugin(): void {
    $this->enableModules(['ckeditor4to5upgrade_plugin_test']);
    \Drupal::state()->set('ckeditor4to5upgrade_plugin_test', 'lying_subset');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The "foo" CKEditor4To5Upgrade plugin claims to provide an upgrade path for the "foo" CKEditor 4 plugin settings but does not.');

    $this->upgradePluginManager->computeCKEditor5PluginSubsetConfiguration('foo', FilterFormat::create());
  }

}
