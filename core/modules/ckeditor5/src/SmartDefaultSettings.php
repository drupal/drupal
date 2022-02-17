<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5;

use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\FilterFormatInterface;

/**
 * Generates CKEditor 5 settings for existing text editors/formats.
 *
 * @internal
 *   This class may change at any time. It is not for use outside this module.
 */
final class SmartDefaultSettings {

  use StringTranslationTrait;

  /**
   * The CKEditor 5 plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The CKEditor 4 to 5 upgrade plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $upgradePluginManager;

  /**
   * The "CKEditor 4 plugin" plugin manager.
   *
   * @var \Drupal\ckeditor\CKEditorPluginManager
   */
  protected $cke4PluginManager;

  /**
   * Constructs a SmartDefaultSettings object.
   *
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface $plugin_manager
   *   The CKEditor 5 plugin manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $upgrade_plugin_manager
   *   The CKEditor 4 to 5 upgrade plugin manager.
   * @param \Drupal\ckeditor\CKEditorPluginManager $cke4_plugin_manager
   *   The CKEditor 4 plugin manager.
   */
  public function __construct(CKEditor5PluginManagerInterface $plugin_manager, PluginManagerInterface $upgrade_plugin_manager, CKEditorPluginManager $cke4_plugin_manager = NULL) {
    $this->pluginManager = $plugin_manager;
    $this->upgradePluginManager = $upgrade_plugin_manager;
    $this->cke4PluginManager = $cke4_plugin_manager;
  }

  /**
   * Computes the closest possible equivalent settings for switching to CKEditor 5.
   *
   * @param \Drupal\editor\EditorInterface|null $text_editor
   *   The editor being reconfigured for CKEditor 5 to match the CKEditor 4
   *   settings as closely as possible (if it was using CKEditor 4).
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The text format for which to compute smart default settings.
   *
   * @return array
   *   An array with two values:
   *   1. The cloned config entity with settings modified for CKEditor 5 … or a
   *      completely new config entity if this text format did not yet have one.
   *   2. Messages explaining what conclusions were reached.
   *
   * @throws \InvalidArgumentException
   *   Thrown when computing smart default settings for a new text format, or
   *   when the given text editor and format do not form a pair.
   */
  public function computeSmartDefaultSettings(?EditorInterface $text_editor, FilterFormatInterface $text_format): array {
    if ($text_format->isNew()) {
      throw new \InvalidArgumentException('Smart default settings can only be computed when there is a pre-existing text format.');
    }
    if ($text_editor && $text_editor->id() !== $text_format->id()) {
      throw new \InvalidArgumentException('The given text editor and text format must form a pair.');
    }

    $messages = [];

    // Ensure that unsaved changes to the text format object are also respected.
    if ($text_editor) {
      // Overwrite the Editor config entity object's $filterFormat property, to
      // prevent calls to Editor::hasAssociatedFilterFormat() and
      // Editor::getFilterFormat() from loading the FilterFormat from storage.
      // @todo Remove in https://www.drupal.org/project/ckeditor5/issues/3218985.
      $reflector = new \ReflectionObject($text_editor);
      $property = $reflector->getProperty('filterFormat');
      $property->setAccessible(TRUE);
      $property->setValue($text_editor, $text_format);
    }

    // When there is a pre-existing text editor, pass that. Otherwise, generate
    // an empty shell of a text editor config entity — this will then
    // automatically get the default CKEditor 5 settings.
    // @todo Update after https://www.drupal.org/project/drupal/issues/3226673.
    /** @var \Drupal\editor\Entity\Editor $editor */
    $editor = $text_editor !== NULL
      ? clone $text_editor
      : Editor::create([
        'format' => $text_format->id(),
        // @see \Drupal\editor\Entity\Editor::__construct()
        // @see \Drupal\ckeditor5\Plugin\Editor\CKEditor5::getDefaultSettings()
        'editor' => 'ckeditor5',
      ]);
    $editor->setEditor('ckeditor5');

    // Compute the appropriate settings based on the CKEditor 4 configuration
    // if it exists.
    $old_editor = $editor->id() ? Editor::load($editor->id()) : NULL;
    if ($old_editor && $old_editor->getEditor() === 'ckeditor') {
      $enabled_cke4_plugins = $this->getEnabledCkeditor4Plugins($old_editor);
      [$upgraded_settings, $messages] = $this->createSettingsFromCKEditor4($old_editor->getSettings(), $enabled_cke4_plugins);
      $editor->setSettings($upgraded_settings);
      $editor->setImageUploadSettings($old_editor->getImageUploadSettings());
    }

    // First, add toolbar items based on HTML tags.
    // NOTE: Helper updates $editor->settings by reference and returns info for the message.
    $result = $this->addToolbarItemsToMatchHtmlTagsInFormat($text_format, $editor);
    if ($result !== NULL) {
      [$enabling_message_content, $unsupported] = $result;
      if ($enabling_message_content) {
        $messages[] = $this->t('The following plugins were enabled to support tags that are allowed by this text format: %enabling_message_content.',
          ['%enabling_message_content' => $enabling_message_content],
        );
      }
      // Warn user about unsupported tags.
      if (!empty($unsupported)) {
        $this->addTagsToSourceEditing($editor, $unsupported);
        $messages[] = $this->t("The following tags were permitted by this format's filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: @unsupported_string.", [
          '@unsupported_string' => $unsupported->toFilterHtmlAllowedTagsString(),
        ]);
      }
    }

    // Next, add more toolbar items to try to also support attributes on already
    // supported tags that have still unsupported attributes.
    $result = $this->addToolbarItemsToMatchHtmlAttributesInFormat($text_format, $editor);
    if ($result !== NULL) {
      [$enabled_for_attributes_message_content, $missing_attributes] = $result;
      if ($enabled_for_attributes_message_content) {
        $messages[] = $this->t('The following plugins were enabled to support specific attributes that are allowed by this text format: %enabled_for_attributes_message_content.',
          ['%enabled_for_attributes_message_content' => $enabled_for_attributes_message_content],
        );
      }
      // Warn user about supported tags but missing attributes.
      if ($missing_attributes) {
        $this->addTagsToSourceEditing($editor, $missing_attributes);
        $messages[] = $this->t("This format's HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: @missing_attributes.", [
          '@missing_attributes' => $missing_attributes->toFilterHtmlAllowedTagsString(),
        ]);
      }
    }

    // Finally: for all enabled plugins, find the ones that are configurable,
    // and add their default settings. For enabled plugins with element subsets,
    // compute the appropriate settings to achieve the subset that matches the
    // original text format restrictions.
    $this->addDefaultSettingsForEnabledConfigurablePlugins($editor);
    $this->computeSubsetSettingForEnabledPluginsWithSubsets($editor, $text_format);

    return [$editor, $messages];
  }

  private function addTagsToSourceEditing(EditorInterface $editor, HTMLRestrictions $tags): array {
    $messages = [];
    $settings = $editor->getSettings();
    if (!isset($settings['toolbar']['items']) || !in_array('sourceEditing', $settings['toolbar']['items'])) {
      $messages[] = $this->t('The <em>Source Editing</em> plugin was enabled to support tags and/or attributes that are not explicitly supported by any available CKEditor 5 plugins.');
      $settings['toolbar']['items'][] = 'sourceEditing';
    }
    $allowed_tags_array = $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] ?? [];
    $allowed_tags_string = implode(' ', $allowed_tags_array);
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = HTMLRestrictions::fromString($allowed_tags_string)->merge($tags)->toCKEditor5ElementsArray();
    $editor->setSettings($settings);
    return $messages;
  }

  /**
   * Creates equivalent CKEditor 5 settings from CKEditor 4 settings.
   *
   * @param array $ckeditor4_settings
   *   The value for "settings" in a Text Editor config entity configured to use
   *   CKEditor 4.
   * @param string[] $enabled_ckeditor4_plugins
   *   The list of enabled CKEditor 4 plugins: their settings will be mapped to
   *   the CKEditor 5 equivalents, if they have any.
   *
   * @return array
   *   An array with two values:
   *   1. An equivalent value for CKEditor 5.
   *   2. Messages explaining upgrade path issues.
   *
   * @throws \LogicException
   *   Thrown when an upgrade plugin is attempting to generate plugin settings
   *   for a CKEditor 4 plugin upgrade path that have already been generated.
   */
  private function createSettingsFromCKEditor4(array $ckeditor4_settings, array $enabled_ckeditor4_plugins): array {
    $settings = [
      'toolbar' => [
        'items' => [],
      ],
      'plugins' => [],
    ];
    $messages = [];

    // First: toolbar items.
    // @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
    foreach ($ckeditor4_settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        $some_added = FALSE;
        foreach ($group['items'] as $cke4_button) {
          try {
            $equivalent = $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem($cke4_button);
          }
          catch (\OutOfBoundsException $e) {
            $messages[] = $this->t('The CKEditor 4 button %button does not have a known upgrade path. If it allowed editing markup, then you can do so now through the Source Editing functionality.', [
              '%button' => $cke4_button,
            ]);
            continue;
          }
          if ($equivalent) {
            $settings['toolbar']['items'][] = $equivalent;
            $some_added = TRUE;
          }
        }
        // Add a CKEditor 5 toolbar group separator for every group.
        if ($some_added) {
          $settings['toolbar']['items'][] = '|';
        }
      }
    }
    // Remove the trailing CKEditor 5 toolbar group separator.
    array_pop($settings['toolbar']['items']);
    // Strip the CKEditor 4 buttons without a CKEditor 5 equivalent.
    $settings['toolbar']['items'] = array_filter($settings['toolbar']['items']);

    // Second: plugin settings.
    // @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
    $enabled_ckeditor4_plugins_with_settings = array_intersect_key($ckeditor4_settings['plugins'], array_flip($enabled_ckeditor4_plugins));
    foreach ($enabled_ckeditor4_plugins_with_settings as $cke4_plugin_id => $cke4_plugin_settings) {
      try {
        $cke5_plugin_settings = $this->upgradePluginManager->mapCKEditor4SettingsToCKEditor5Configuration($cke4_plugin_id, $cke4_plugin_settings);
        if ($cke5_plugin_settings === NULL) {
          continue;
        }
        assert(count($cke5_plugin_settings) === 1);
        $cke5_plugin_id = array_keys($cke5_plugin_settings)[0];
        if (isset($settings['plugins'][$cke5_plugin_id])) {
          throw new \LogicException(sprintf('The %s plugin settings have already been upgraded. Only a single @CKEditor4To5Upgrade is allowed to migrate the settings for a particular CKEditor 4 plugin.', $cke5_plugin_id));
        }
        $settings['plugins'] += $cke5_plugin_settings;
      }
      catch (\OutOfBoundsException $e) {
        $messages[] = $this->t('The %cke4_plugin_id plugin settings do not have a known upgrade path.', [
          '%cke4_plugin_id' => $cke4_plugin_id,
        ]);
        continue;
      }
    }

    return [$settings, $messages];
  }

  /**
   * Gets all enabled CKEditor 4 plugins.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   A text editor config entity configured to use CKEditor 4.
   *
   * @return string[]
   *   The enabled CKEditor 4 plugin IDs.
   */
  protected function getEnabledCkeditor4Plugins(EditorInterface $editor): array {
    assert($editor->getEditor() === 'ckeditor');

    // This is largely copied from the CKEditor 4 plugin manager, because it
    // unfortunately does not provide the API this needs.
    // @see \Drupal\ckeditor\CKEditorPluginManager::getEnabledPluginFiles()
    $plugins = array_keys($this->cke4PluginManager->getDefinitions());
    $toolbar_buttons = $this->cke4PluginManager->getEnabledButtons($editor);
    $enabled_plugins = [];
    $additional_plugins = [];
    foreach ($plugins as $plugin_id) {
      $plugin = $this->cke4PluginManager->createInstance($plugin_id);

      $enabled = FALSE;
      // Enable this plugin if it provides a button that has been enabled.
      if ($plugin instanceof CKEditorPluginButtonsInterface) {
        $plugin_buttons = array_keys($plugin->getButtons());
        $enabled = (count(array_intersect($toolbar_buttons, $plugin_buttons)) > 0);
      }
      // Otherwise enable this plugin if it declares itself as enabled.
      if (!$enabled && $plugin instanceof CKEditorPluginContextualInterface) {
        $enabled = $plugin->isEnabled($editor);
      }

      if ($enabled) {
        $enabled_plugins[] = $plugin_id;
        // Check if this plugin has dependencies that also need to be enabled.
        $additional_plugins = array_merge($additional_plugins, array_diff($plugin->getDependencies($editor), $additional_plugins));
      }
    }

    // Add the list of dependent plugins.
    foreach ($additional_plugins as $plugin_id) {
      $enabled_plugins[$plugin_id] = $plugin_id;
    }

    return $enabled_plugins;
  }

  /**
   * Adds CKEditor 5 toolbar items to match the format's HTML tags.
   *
   * @param \Drupal\filter\FilterFormatInterface $format
   *   The text format for which to compute smart default settings.
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor config entity to update.
   *
   * @return array|null
   *   NULL when nothing happened, otherwise an array with two values:
   *   1. a description (for use in a message) of which CKEditor 5 plugins were
   *      enabled to match the HTML tags allowed by the text format.
   *   2. the unsupported elements, in an HTMLRestrictions value object
   */
  private function addToolbarItemsToMatchHtmlTagsInFormat(FilterFormatInterface $format, EditorInterface $editor): ?array {
    $html_restrictions_needed_elements = $format->getHtmlRestrictions();
    if ($html_restrictions_needed_elements === FALSE) {
      return NULL;
    }

    // Add all buttons until we match or exceed the current text format
    // restrictions.
    $enabled_plugins = array_keys($this->pluginManager->getEnabledDefinitions($editor));
    $provided_elements = $this->pluginManager->getProvidedElements($enabled_plugins);

    // Automatically add the plugins that add support for the tags we want this
    // CKEditor 5 instance to support.
    $missing_tags = array_diff(array_keys($html_restrictions_needed_elements['allowed']), array_keys($provided_elements));
    $to_add = [];
    $unsupported = [];
    foreach ($missing_tags as $tag) {
      $id = $this->pluginManager->findPluginSupportingElement($tag);
      if ($id) {
        $to_add[$tag] = $id;
      }
      // Add any tag that isn't the "star protector" tag to the array of
      // unsupported tags.
      // @see the $star_protector variable in
      // \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions
      // @todo this can be an 'else' with no conditions after
      // https://www.drupal.org/project/drupal/issues/3226368
      elseif ($tag !== '__zqh6vxfbk3cg__') {
        $unsupported[$tag] = $html_restrictions_needed_elements['allowed'][$tag];
      }
    }

    $enabling_message_content = '';
    $enabling_message_prep = [];
    foreach ($to_add as $tag_name => $plugin_name) {
      $enabling_message_prep[$plugin_name][] = $tag_name;
    }

    $editor_settings_to_update = $editor->getSettings();
    $new_group_created = FALSE;
    foreach ($enabling_message_prep as $plugin_id => $tag_names) {
      $label = $this->pluginManager->getDefinition($plugin_id)->label();
      $tags = array_reduce($tag_names, function ($carry, $item) {
        return $carry . "<$item>";
      });
      $enabling_message_content .= "$label (for tags: $tags) ";
      $definition = $this->pluginManager->getDefinition($plugin_id);
      if ($definition->hasToolbarItems()) {
        if (!$new_group_created) {
          $editor_settings_to_update['toolbar']['items'][] = '|';
          $new_group_created = TRUE;
        }
        $editor_settings_to_update['toolbar']['items'] = array_merge($editor_settings_to_update['toolbar']['items'], array_keys($definition->getToolbarItems()));
      }
    }

    unset($unsupported['*']);
    if (!empty($enabling_message_content)) {
      $editor->setSettings($editor_settings_to_update);
      $enabling_message_content = substr($enabling_message_content, 0, -1);
      return [$enabling_message_content, new HTMLRestrictions($unsupported)];
    }
    else {
      return [NULL, new HTMLRestrictions($unsupported)];
    }
  }

  /**
   * Adds CKEditor 5 toolbar items to match the format's HTML attributes.
   *
   * @param \Drupal\filter\FilterFormatInterface $format
   *   The text format for which to compute smart default settings.
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor config entity to update.
   *
   * @return array|null
   *   NULL when nothing happened, otherwise an array with two values:
   *   1. a description (for use in a message) of which CKEditor 5 plugins were
   *      enabled to match the HTML attributes allowed by the text format.
   *   2. the unsupported elements, in an HTMLRestrictions value object
   */
  private function addToolbarItemsToMatchHtmlAttributesInFormat(FilterFormatInterface $format, EditorInterface $editor): ?array {
    $html_restrictions_needed_elements = $format->getHtmlRestrictions();
    if ($html_restrictions_needed_elements === FALSE) {
      return NULL;
    }

    $enabled_plugins = array_keys($this->pluginManager->getEnabledDefinitions($editor));
    $provided_elements = $this->pluginManager->getProvidedElements($enabled_plugins);
    $provided = new HTMLRestrictions($provided_elements);
    $missing = HTMLRestrictions::fromTextFormat($format)->diff($provided);
    $supported_tags_with_unsupported_attributes = array_intersect_key($missing->getAllowedElements(), $provided_elements);
    $supported_tags_with_unsupported_attributes = array_filter($supported_tags_with_unsupported_attributes, function ($tag_config) {
      return is_array($tag_config);
    });
    $still_needed = new HTMLRestrictions($supported_tags_with_unsupported_attributes);

    if (!$still_needed->isEmpty()) {
      $all_plugins_definitions = $this->pluginManager->getDefinitions();
      foreach ($all_plugins_definitions as $plugin_id => $definition) {
        // Only proceed if the plugin has configured elements and the plugin
        // does not have conditions. In the future we could add support for
        // automatically enabling filters, but for now we assume that the filter
        // configuration cannot be modified.
        if (!in_array($plugin_id, $enabled_plugins, TRUE) && !$definition->hasConditions() && $definition->hasElements()) {
          $plugin_support = HTMLRestrictions::fromString(implode(' ', $definition->getElements()));
          // Do not inspect just $plugin_support, but the union of that with the
          // already supported elements: wildcard restrictions will only resolve
          // if the concrete tags they support are also present.
          $potential_future = $provided->merge($plugin_support);
          // This is the heart of the operation: intersect the potential future
          // with what we need to achieve, then subtract what is already
          // supported. This yields the net new elements.
          $net_new = $potential_future->intersect($still_needed)->diff($provided);
          if (!$net_new->isEmpty()) {
            foreach ($net_new->getAllowedElements() as $tag_name => $attributes_config) {
              foreach ($attributes_config as $attribute_name => $attribute_config) {
                $plugins_to_enable_to_support_attribute_config[$plugin_id][$attribute_name][$tag_name] = $attribute_config;
              }
            }
            // Fewer attributes are still needed.
            $still_needed = $still_needed->diff($net_new);
          }
        }
      }

      // If additional plugins need to be enable to support attribute config,
      // loop through the list to enable the plugins and build a UI message that
      // will convey this plugin-enabling to the user.
      if (!empty($plugins_to_enable_to_support_attribute_config)) {
        $enabled_for_attributes_message_content = '';
        $editor_settings_to_update = $editor->getSettings();
        foreach ($plugins_to_enable_to_support_attribute_config as $plugin_id => $reason_why_enabled) {
          $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
          $label = $plugin_definition->label();
          if ($plugin_definition->hasToolbarItems()) {
            $editor_settings_to_update['toolbar']['items'] = array_merge($editor_settings_to_update['toolbar']['items'], array_keys($plugin_definition->getToolbarItems()));
            foreach ($reason_why_enabled as $attribute_name => $attribute_config) {
              $enabled_for_attributes_message_content .= "$label (";
              foreach ($attribute_config as $tag_name => $attribute_value_config) {
                $enabled_for_attributes_message_content .= " for tag: <$tag_name> to support: $attribute_name";
                if (is_array($attribute_value_config)) {
                  $enabled_for_attributes_message_content .= " with value(s): ";
                  foreach (array_keys($attribute_value_config) as $allowed_value) {
                    $enabled_for_attributes_message_content .= " $allowed_value,";
                  }
                  $enabled_for_attributes_message_content = substr($enabled_for_attributes_message_content, 0, -1) . '), ';
                }
              }
            }
          }
        }
        $editor->setSettings($editor_settings_to_update);
        // Some plugins enabled, maybe some missing attributes.
        return [
          substr($enabled_for_attributes_message_content, 0, -2),
          $still_needed,
        ];
      }
      else {
        // No plugins enabled, maybe some missing attributes.
        return [
          NULL,
          $still_needed,
        ];
      }
    }
    else {
      return NULL;
    }
  }

  /**
   * Adds default settings for all enabled CKEditor 5 plugins.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor config entity to update.
   */
  private function addDefaultSettingsForEnabledConfigurablePlugins(EditorInterface $editor): void {
    $settings = $editor->getSettings();
    $update_settings = FALSE;
    $enabled_definitions = $this->pluginManager->getEnabledDefinitions($editor);
    $configurable_definitions = array_filter($enabled_definitions, function (CKEditor5PluginDefinition $definition): bool {
      return $definition->isConfigurable();
    });

    foreach ($configurable_definitions as $plugin_name => $definition) {
      // Skip image upload as its configuration is stored in a discrete
      // property of the $editor object, not its settings. Also skip any plugin
      // that already has configuration data as default values are not needed.
      if ($plugin_name === 'ckeditor5_imageUpload' || isset($settings['plugins'][$plugin_name])) {
        continue;
      }
      $update_settings = TRUE;
      $settings['plugins'][$plugin_name] = $this->pluginManager->getPlugin($plugin_name, NULL)->defaultConfiguration();
    }

    if ($update_settings) {
      $editor->setSettings($settings);
    }
  }

  /**
   * Computes configuration for all enabled CKEditor 5 plugins with subsets.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor config entity to update.
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The text format for which to compute smart default settings.
   */
  private function computeSubsetSettingForEnabledPluginsWithSubsets(EditorInterface $editor, FilterFormatInterface $text_format): void {
    $settings = $editor->getSettings();
    $update_settings = FALSE;
    $enabled_definitions = $this->pluginManager->getEnabledDefinitions($editor);
    $configurable_subset_definitions = array_filter($enabled_definitions, function (CKEditor5PluginDefinition $definition): bool {
      return is_a($definition->getClass(), CKEditor5PluginElementsSubsetInterface::class, TRUE);
    });

    foreach ($configurable_subset_definitions as $plugin_name => $definition) {
      // Skip Source Editing as that has already been configured.
      if ($plugin_name === 'ckeditor5_sourceEditing') {
        continue;
      }

      try {
        $subset_configuration = $this->upgradePluginManager->computeCKEditor5PluginSubsetConfiguration($plugin_name, $text_format);
      }
      catch (\OutOfBoundsException $e) {
        $messages[] = $this->t('The CKEditor 5 plugin %button has a configurable subset of elements, but does not have a known upgrade path to configure that subset to match your text format. Hence it is now using its default configuration.', [
          '%plugin' => $plugin_name,
        ]);
        continue;
      }
      if ($subset_configuration) {
        $update_settings = TRUE;
        assert(isset($settings['plugins'][$plugin_name]));
        $default_configuration = $settings['plugins'][$plugin_name];
        // The subset configuration's key-value pairs must override those of the
        // default configuration.
        $settings['plugins'][$plugin_name] = $subset_configuration + $default_configuration;
      }
    }

    if ($update_settings) {
      $editor->setSettings($settings);
    }
  }

}
