<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\FilterFormatInterface;
use Psr\Log\LoggerInterface;

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SmartDefaultSettings object.
   *
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface $plugin_manager
   *   The CKEditor 5 plugin manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $upgrade_plugin_manager
   *   The CKEditor 4 to 5 upgrade plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(CKEditor5PluginManagerInterface $plugin_manager, PluginManagerInterface $upgrade_plugin_manager, LoggerInterface $logger, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->pluginManager = $plugin_manager;
    $this->upgradePluginManager = $upgrade_plugin_manager;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
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
      // @todo Remove in https://www.drupal.org/project/drupal/issues/3231347.
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

    $source_editing_additions = HTMLRestrictions::emptySet();
    // Compute the appropriate settings based on the CKEditor 4 configuration
    // if it exists.
    $old_editor = $editor->id() ? Editor::load($editor->id()) : NULL;
    $old_editor_restrictions = $old_editor ? HTMLRestrictions::fromTextFormat($old_editor->getFilterFormat()) : HTMLRestrictions::emptySet();
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3245351
    if ($old_editor) {
      $editor->setImageUploadSettings($old_editor->getImageUploadSettings());
    }
    if ($old_editor && $old_editor->getEditor() === 'ckeditor') {
      [$upgraded_settings, $messages] = $this->createSettingsFromCKEditor4($old_editor->getSettings(), HTMLRestrictions::fromTextFormat($old_editor->getFilterFormat()));
      $editor->setSettings($upgraded_settings);
      // *Before* determining which elements are still needed for this text
      // format, ensure that all already enabled plugins that are configurable
      // have valid settings.
      // For all already enabled plugins, find the ones that are configurable,
      // and add their default settings. For enabled plugins with element
      // subsets, compute the appropriate settings to achieve the subset that
      // matches the original text format restrictions.
      $this->addDefaultSettingsForEnabledConfigurablePlugins($editor);
      $this->computeSubsetSettingForEnabledPluginsWithSubsets($editor, $text_format);
    }

    // Add toolbar items based on HTML tags and attributes.
    // NOTE: Helper updates $editor->settings by reference and returns info for the message.
    $result = $this->addToolbarItemsToMatchHtmlElementsInFormat($text_format, $editor);
    if ($result !== NULL) {
      [$enabling_message_content, $enabled_for_attributes_message_content, $missing, $plugins_enabled] = $result;

      // Distinguish between unsupported elements covering only tags or not.
      $missing_attributes = new HTMLRestrictions(array_filter($missing->getAllowedElements()));
      $unsupported = $missing->diff($missing_attributes);

      if ($enabling_message_content) {
        $this->logger->info(new FormattableMarkup('The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the %text_format text format: %enabling_message_content. The text format must be saved to make these changes active.',
          [
            '%text_format' => $editor->getFilterFormat()->get('name'),
            '%enabling_message_content' => $enabling_message_content,
          ],
        ));
      }
      // Warn user about unsupported tags.
      if (!$unsupported->allowsNothing()) {
        $this->addTagsToSourceEditing($editor, $unsupported);
        $source_editing_additions = $source_editing_additions->merge($unsupported);
        $this->logger->info(new FormattableMarkup("The following tags were permitted by the %text_format text format's filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: @unsupported_string. The text format must be saved to make these changes active.", [
          '%text_format' => $editor->getFilterFormat()->get('name'),
          '@unsupported_string' => $unsupported->toFilterHtmlAllowedTagsString(),
        ]));
      }

      if ($enabled_for_attributes_message_content) {
        $this->logger->info(new FormattableMarkup('The CKEditor 5 migration process enabled the following plugins to support specific attributes that are allowed by the %text_format text format: %enabled_for_attributes_message_content.',
          [
            '%text_format' => $editor->getFilterFormat()->get('name'),
            '%enabled_for_attributes_message_content' => $enabled_for_attributes_message_content,
          ],
        ));
      }
      // Warn user about supported tags but missing attributes.
      if (!$missing_attributes->allowsNothing()) {
        $this->addTagsToSourceEditing($editor, $missing_attributes);
        $source_editing_additions = $source_editing_additions->merge($missing_attributes);
        $this->logger->info(new FormattableMarkup("As part of migrating to CKEditor 5, it was found that the %text_format text format's HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: @missing_attributes. The text format must be saved to make these changes active.", [
          '%text_format' => $editor->getFilterFormat()->get('name'),
          '@missing_attributes' => $missing_attributes->toFilterHtmlAllowedTagsString(),
        ]));
      }
    }

    $has_html_restrictions = $editor->getFilterFormat()->filters('filter_html')->status;
    $missing_fundamental_tags = HTMLRestrictions::emptySet();
    if ($has_html_restrictions) {
      $fundamental = new HTMLRestrictions($this->pluginManager->getProvidedElements([
        'ckeditor5_essentials',
        'ckeditor5_paragraph',
      ]));
      $filter_html_restrictions = HTMLRestrictions::fromTextFormat($editor->getFilterFormat());
      $missing_fundamental_tags = $fundamental->diff($filter_html_restrictions);
      if (!$missing_fundamental_tags->allowsNothing()) {
        $editor->getFilterFormat()->setFilterConfig('filter_html', $filter_html_restrictions->merge($fundamental)->getAllowedElements());
        $this->logger->warning(new FormattableMarkup("As part of migrating the %text_format text format to CKEditor 5, the following tag(s) were added to <em>Limit allowed HTML tags and correct faulty HTML</em>, because they are needed to provide fundamental CKEditor 5 functionality : @missing_tags. The text format must be saved to make these changes active.", [
          '%text_format' => $editor->getFilterFormat()->get('name'),
          '@missing_tags' => $missing_fundamental_tags->toFilterHtmlAllowedTagsString(),
        ]));
      }
    }

    // Finally: for all enabled plugins, find the ones that are configurable,
    // and add their default settings. For enabled plugins with element subsets,
    // compute the appropriate settings to achieve the subset that matches the
    // original text format restrictions.
    // Note: if switching from CKEditor 4, this will already have happened for
    // plugins that were already enabled in CKEditor 4. It's harmless to compute
    // this again.
    $this->addDefaultSettingsForEnabledConfigurablePlugins($editor);
    $this->computeSubsetSettingForEnabledPluginsWithSubsets($editor, $text_format);

    // In CKEditor 4, it's possible for settings to exist for plugins that are
    // not actually enabled. During the upgrade path, these would then be mapped
    // to equivalent CKEditor 5 configuration. But CKEditor 5 does not allow
    // configuration to be stored for disabled plugins. Therefore determine
    // which plugins actually are enabled, and omit the (upgraded) plugin
    // configuration for disabled plugins.
    // @see \Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface::mapCKEditor4SettingsToCKEditor5Configuration()
    if ($old_editor && $old_editor->getEditor() === 'ckeditor') {
      $enabled_definitions = $this->pluginManager->getEnabledDefinitions($editor);
      $enabled_configurable_definitions = array_filter($enabled_definitions, function (CKEditor5PluginDefinition $definition): bool {
        return is_a($definition->getClass(), CKEditor5PluginConfigurableInterface::class, TRUE);
      });
      $settings = $editor->getSettings();
      $settings['plugins'] = array_intersect_key($settings['plugins'], $enabled_configurable_definitions);
      $editor->setSettings($settings);
    }

    if ($has_html_restrictions) {
      // Determine what tags/attributes are allowed in this text format that were
      // not allowed previous to the switch.
      $allowed_by_new_plugin_config = new HTMLRestrictions($this->pluginManager->getProvidedElements(array_keys($this->pluginManager->getEnabledDefinitions($editor)), $editor));
      $surplus_tags_attributes = $allowed_by_new_plugin_config->diff($old_editor_restrictions)->diff($missing_fundamental_tags);
      $attributes_to_tag = [];
      $added_tags = [];
      if (!$surplus_tags_attributes->allowsNothing()) {
        $surplus_elements = $surplus_tags_attributes->getAllowedElements();
        $added_tags = array_diff_key($surplus_elements, $old_editor_restrictions->getAllowedElements());
        foreach ($surplus_elements as $tag => $attributes) {
          $the_attributes = is_array($attributes) ? $attributes : [];
          foreach ($the_attributes as $attribute_name => $enabled) {
            if ($enabled) {
              $attributes_to_tag[$attribute_name][] = $tag;
            }
          }
        }
      }

      $help_enabled = $this->moduleHandler->moduleExists('help');
      $can_access_dblog = ($this->currentUser->hasPermission('access site reports') && $this->moduleHandler->moduleExists('dblog'));

      if (!empty($plugins_enabled) || !$source_editing_additions->allowsNothing()) {
        $beginning = $help_enabled ?
          $this->t('To maintain the capabilities of this text format, <a target="_blank" href=":ck_migration_url">the CKEditor 5 migration</a> did the following:', [
            ':ck_migration_url' => Url::fromRoute('help.page', ['name' => 'ckeditor5'], ['fragment' => 'migration-settings'])->toString(),
          ]) :
          $this->t('To maintain the capabilities of this text format, the CKEditor 5 migration did the following:');

        $plugin_info = !empty($plugins_enabled) ?
          $this->t('Enabled these plugins: (%plugins).', [
            '%plugins' => implode(', ', $plugins_enabled),
          ]) : '';

        $source_editing_info = '';
        if (!$source_editing_additions->allowsNothing()) {
          $source_editing_info = $help_enabled ?
            $this->t('Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href=":source_edit_url">Manually editable HTML tags</a> setting: @tag_list',
              [
                '@tag_list' => $source_editing_additions->toFilterHtmlAllowedTagsString(),
                ':source_edit_url' => Url::fromRoute('help.page', ['name' => 'ckeditor5'], ['fragment' => 'source-editing'])->toString(),
              ]) :
            $this->t("Added these tags/attributes to the Source Editing Plugin's Manually editable HTML tags setting: @tag_list", ['@tag_list' => $source_editing_additions->toFilterHtmlAllowedTagsString()]);
        }

        $end = $can_access_dblog ?
          $this->t('Additional details are available <a target="_blank" href=":dblog_url">in your logs</a>.',
            [
              ':dblog_url' => Url::fromRoute('dblog.overview')
                ->setOption('query', ['type[]' => 'ckeditor5'])
                ->toString(),
            ]
          ) :
          $this->t('Additional details are available in your logs.');

        $messages[MessengerInterface::TYPE_STATUS][] = new FormattableMarkup('@beginning @plugin_info @source_editing_info. @end', [
          '@beginning' => $beginning,
          '@plugin_info' => $plugin_info,
          '@source_editing_info' => $source_editing_info,
          '@end' => $end,
        ]);
      }

      // Generate warning for:
      // - The addition of <p>/<br> due to them being fundamental tags.
      // - The addition of other tags/attributes previously unsupported by the
      //   format.
      if (!$missing_fundamental_tags->allowsNothing() || !empty($attributes_to_tag) || !empty($added_tags)) {
        $beginning = $this->t('Updating to CKEditor 5 added support for some previously unsupported tags/attributes.');
        $fundamental_tags = '';
        if ($help_enabled && !$missing_fundamental_tags->allowsNothing()) {
          $fundamental_tags = $this->formatPlural(count($missing_fundamental_tags->toCKEditor5ElementsArray()),
            'The @tag tag was added because it is <a target="_blank" href=":fundamental_tag_link">required by CKEditor 5</a>.',
            'The @tag tags were added because they are <a target="_blank" href=":fundamental_tag_link">required by CKEditor 5</a>.',
            [
              '@tag' => implode(', ', $missing_fundamental_tags->toCKEditor5ElementsArray()),
              ':fundamental_tag_link' => URL::fromRoute('help.page', ['name' => 'ckeditor5'], ['fragment' => 'required-tags'])->toString(),
            ]);
        }
        elseif (!$missing_fundamental_tags->allowsNothing()) {
          $fundamental_tags = $this->formatPlural(count($missing_fundamental_tags->toCKEditor5ElementsArray()),
            'The @tag tag was added because it is required by CKEditor 5.',
            'The @tag tags were added because they are required by CKEditor 5.',
            [
              '@tag' => implode(', ', $missing_fundamental_tags->toCKEditor5ElementsArray()),
            ]);
        }

        $added_elements_begin = !empty($attributes_to_tag) || !empty($added_tags) ? $this->t('A plugin introduced support for the following:') : '';
        $added_elements_tags = !empty($added_tags) ? $this->formatPlural(
          count($added_tags),
          'The tag %tags;',
          'The tags %tags;',
          [
            '%tags' => implode(', ', array_map(function ($tag_name) {
              return "<$tag_name>";
            }, array_keys($added_tags))),
          ]) : '';
        $added_elements_attributes = !empty($attributes_to_tag) ? $this->formatPlural(
          count($attributes_to_tag),
          'This attribute: %attributes;',
          'These attributes: %attributes;',
          [
            '%attributes' => rtrim(array_reduce(array_keys($attributes_to_tag), function ($carry, $item) use ($attributes_to_tag) {
              $for_tags = implode(', ', array_map(function ($item) {
                return "<$item>";
              }, $attributes_to_tag[$item]));
              return "$carry $item ({$this->t('for', [],  ['context' => 'Ckeditor 5 tag list'])} $for_tags),";
            }, ''), " ,"),
          ]
        ) : '';
        $end = $can_access_dblog ?
          $this->t('Additional details are available <a target="_blank" href=":dblog_url">in your logs</a>.',
            [
              ':dblog_url' => Url::fromRoute('dblog.overview')
                ->setOption('query', ['type[]' => 'ckeditor5'])
                ->toString(),
            ]
          ) :
          $this->t('Additional details are available in your logs.');

        $messages[MessengerInterface::TYPE_WARNING][] = new FormattableMarkup('@beginning @added_elements_begin @fundamental_tags @added_elements_tags @added_elements_attributes @end',
          [
            '@beginning' => $beginning,
            '@added_elements_begin' => $added_elements_begin,
            '@fundamental_tags' => $fundamental_tags,
            '@added_elements_tags' => $added_elements_tags,
            '@added_elements_attributes' => $added_elements_attributes,
            '@end' => $end,
          ]);
      }
    }

    return [$editor, $messages];
  }

  private function addTagsToSourceEditing(EditorInterface $editor, HTMLRestrictions $tags): array {
    $messages = [];
    $settings = $editor->getSettings();
    if (!isset($settings['toolbar']['items']) || !in_array('sourceEditing', $settings['toolbar']['items'])) {
      $messages[MessengerInterface::TYPE_STATUS][] = $this->t('The <em>Source Editing</em> plugin was enabled to support tags and/or attributes that are not explicitly supported by any available CKEditor 5 plugins.');
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
   * @param \Drupal\ckeditor5\HTMLRestrictions $text_format_html_restrictions
   *   The restrictions of the text format, to allow an upgrade plugin to
   *   inspect the text format's HTML restrictions to make a decision.
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
  private function createSettingsFromCKEditor4(array $ckeditor4_settings, HTMLRestrictions $text_format_html_restrictions): array {
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
            $equivalent = $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem($cke4_button, $text_format_html_restrictions);
          }
          catch (\OutOfBoundsException $e) {
            $this->logger->warning(new FormattableMarkup('The CKEditor 4 button %button does not have a known upgrade path. If it allowed editing markup, then you can do so now through the Source Editing functionality.', [
              '%button' => $cke4_button,
            ]));
            $messages[MessengerInterface::TYPE_WARNING][] = $this->t('The CKEditor 4 button %button does not have a known upgrade path. If it allowed editing markup, then you can do so now through the Source Editing functionality.', [
              '%button' => $cke4_button,
            ]);
            continue;
          }
          if ($equivalent) {
            $settings['toolbar']['items'] = array_merge($settings['toolbar']['items'], $equivalent);
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
    $enabled_ckeditor4_plugins_with_settings = $ckeditor4_settings['plugins'];
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
        $this->logger->warning(new FormattableMarkup('The %cke4_plugin_id plugin settings do not have a known upgrade path.', [
          '%cke4_plugin_id' => $cke4_plugin_id,
        ]));
        $messages[MessengerInterface::TYPE_WARNING][] = $this->t('The %cke4_plugin_id plugin settings do not have a known upgrade path.', [
          '%cke4_plugin_id' => $cke4_plugin_id,
        ]);
        continue;
      }
    }

    return [$settings, $messages];
  }

  /**
   * Computes net new needed elements when considering adding the given plugin.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $baseline
   *   The set of HTML restrictions already supported.
   * @param \Drupal\ckeditor5\HTMLRestrictions $needed
   *   The set of HTML restrictions that are needed, that is: in addition to
   *   $baseline.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $added_plugin
   *   The CKEditor 5 plugin that is being evaluated to check if it would meet
   *   some of the needs.
   *
   * @return array
   *   An array containing two values:
   *   - a set of HTML restrictions that indicates the net new additions that
   *     are needed
   *   - a set of HTML restrictions that indicates the surplus additions (these
   *     are elements that were not needed, but are added by this plugin)
   */
  private static function computeNetNewElementsForPlugin(HTMLRestrictions $baseline, HTMLRestrictions $needed, CKEditor5PluginDefinition $added_plugin): array {
    $plugin_support = HTMLRestrictions::fromString(implode(' ', $added_plugin->getElements()));
    // Do not inspect just $plugin_support, but the union of that with the
    // already supported elements: wildcard restrictions will only resolve
    // if the concrete tags they support are also present.
    $potential_future = $baseline->merge($plugin_support);
    // This is the heart of the operation: intersect the potential future
    // with what we need to achieve, then subtract what is already
    // supported. This yields the net new elements.
    $net_new = $potential_future->intersect($needed)->diff($baseline);
    // But … we may compute too many.
    $surplus_additions = $potential_future->diff($needed)->diff($baseline);
    return [$net_new, $surplus_additions];
  }

  /**
   * Computes a score for the given surplus compared to the given need.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $surplus
   *   A surplus compared to what is needed.
   * @param \Drupal\ckeditor5\HTMLRestrictions $needed
   *   Exactly what is needed.
   *
   * @return int
   *   A surplus score. Lower is better. Scores are a positive integer.
   *
   * @see https://www.drupal.org/project/drupal/issues/3231328#comment-14444987
   */
  private static function computeSurplusScore(HTMLRestrictions $surplus, HTMLRestrictions $needed): int {
    // Compute a score for surplus elements, while taking into account how much
    // impact each surplus element has:
    $surplus_score = 0;
    foreach ($surplus->getAllowedElements() as $tag_name => $attributes_config) {
      // 10^6 per surplus tag.
      if (!isset($needed->getAllowedElements()[$tag_name])) {
        $surplus_score += pow(10, 6);
      }

      // 10^5 per surplus "any attributes allowed".
      if ($attributes_config === TRUE) {
        $surplus_score += pow(10, 5);
      }

      if (!is_array($attributes_config)) {
        continue;
      }

      foreach ($attributes_config as $attribute_name => $attribute_config) {
        // 10^4 per surplus wildcard attribute.
        if (strpos($attribute_name, '*') !== FALSE) {
          $surplus_score += pow(10, 4);
        }
        // 10^3 per surplus attribute.
        else {
          $surplus_score += pow(10, 3);
        }

        // 10^2 per surplus "any attribute values allowed".
        if ($attribute_config === TRUE) {
          $surplus_score += pow(10, 2);
        }

        if (!is_array($attribute_config)) {
          continue;
        }

        foreach ($attribute_config as $allowed_attribute_value => $allowed_attribute_value_config) {
          // 10^1 per surplus wildcard attribute value.
          if (strpos($allowed_attribute_value, '*') !== FALSE) {
            $surplus_score += pow(10, 1);
          }
          // 10^0 per surplus attribute value.
          else {
            $surplus_score += pow(10, 0);
          }
        }
      }
    }
    return $surplus_score;
  }

  /**
   * Finds candidates for the still needed restrictions among disabled plugins.
   *
   * @param \Drupal\ckeditor5\HTMLRestrictions $provided
   *   The already provided HTML restrictions, thanks to already enabled
   *   CKEditor 5 plugins.
   * @param \Drupal\ckeditor5\HTMLRestrictions $still_needed
   *   The still needed HTML restrictions, unmet by the already enabled CKEditor
   *   5 plugins.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[] $disabled_plugin_definitions
   *   The list of not yet enabled CKEditor 5 plugin definitions, amongst which
   *   candidates must be found.
   *
   * @return array
   *   A nested array with a tree structure covering:
   *   1. tag name
   *   2. concrete attribute name, `-attribute-none-` (meaning no attributes
   *      allowed on this tag) or `-attribute-any-` (meaning any attribute
   *      allowed on this tag).
   *   3. (optional) attribute value (if concrete attribute name in previous
   *      level), `TRUE` or `FALSE`
   *   4. (optional) attribute value restriction
   *   5. candidate CKEditor 5 plugin ID for the HTML elements in the hierarchy
   *   and the surplus score as the value. In other words: the leaf of this is
   *   always a leaf, and a selected CKEditor 5 plugin ID is always the parent
   *   of a leaf.
   */
  private static function getCandidates(HTMLRestrictions $provided, HTMLRestrictions $still_needed, array $disabled_plugin_definitions): array {
    $plugin_candidates = [];
    if (!$still_needed->allowsNothing()) {
      foreach ($disabled_plugin_definitions as $definition) {
        // Only proceed if the plugin has configured elements and the plugin
        // does not have conditions. In the future we could add support for
        // automatically enabling filters, but for now we assume that the filter
        // configuration cannot be modified.
        if (!$definition->hasConditions() && $definition->hasElements()) {
          [$net_new, $surplus_additions] = self::computeNetNewElementsForPlugin($provided, $still_needed, $definition);
          if (!$net_new->allowsNothing()) {
            $plugin_id = $definition->id();
            $creatable_elements = HTMLRestrictions::fromString(implode(' ', $definition->getCreatableElements()));
            $surplus_score = static::computeSurplusScore($surplus_additions, $still_needed);
            foreach ($net_new->getAllowedElements() as $tag_name => $attributes_config) {
              // Non-specific attribute restrictions: `FALSE` or `TRUE`.
              // TRICKY: PHP does not support boolean array keys, so map these
              // to a string. The string must not be a valid attribute name, so
              // use a leading and trailing dash.
              if (!is_array($attributes_config)) {
                if ($attributes_config === FALSE && !array_key_exists($tag_name, $creatable_elements->getAllowedElements())) {
                  // If this plugin is not able to create the plain tag, then
                  // cannot be a candidate for the tag without attributes.
                  continue;
                }
                $non_specific_attribute = $attributes_config ? '-attributes-any-' : '-attributes-none-';
                $plugin_candidates[$tag_name][$non_specific_attribute][$plugin_id] = $surplus_score;
                continue;
              }

              // With specific attribute restrictions: array.
              foreach ($attributes_config as $attribute_name => $attribute_config) {
                if (!is_array($attribute_config)) {
                  $plugin_candidates[$tag_name][$attribute_name][$attribute_config][$plugin_id] = $surplus_score;
                }
                else {
                  foreach ($attribute_config as $allowed_attribute_value => $allowed_attribute_value_config) {
                    $plugin_candidates[$tag_name][$attribute_name][$allowed_attribute_value][$allowed_attribute_value_config][$plugin_id] = $surplus_score;
                  }
                }
              }

              // If this plugin supports unneeded attributes, it still makes a
              // valid candidate for supporting the HTML tag.
              $plugin_candidates[$tag_name]['-attributes-none-'][$plugin_id] = $surplus_score;
            }
          }
        }
      }
    }

    return $plugin_candidates;
  }

  /**
   * Selects best candidate for each of the still needed restrictions.
   *
   * @param array $candidates
   *   The output of ::getCandidates().
   * @param \Drupal\ckeditor5\HTMLRestrictions $still_needed
   *   The still needed HTML restrictions, unmet by the already enabled CKEditor
   *   5 plugins.
   * @param string[] $already_supported_tags
   *   A list of already supported HTML tags, necessary to select the best
   *   matching candidate for elements still needed in $still_needed.
   *
   * @return array
   *   A nested array with a tree structure, with each key a selected CKEditor 5
   *   plugin ID and its values expressing the reason it was enabled.
   */
  private static function selectCandidate(array $candidates, HTMLRestrictions $still_needed, array $already_supported_tags): array {
    assert(Inspector::assertAllStrings($already_supported_tags));

    // Make a selection in the candidates: minimize the surplus count, to
    // avoid generating surplus additions whenever possible.
    $selected_plugins = [];
    foreach ($still_needed->getAllowedElements() as $tag_name => $attributes_config) {
      if (!isset($candidates[$tag_name])) {
        // Sadly no plugin found for this tag.
        continue;
      }

      // Non-specific attribute restrictions for tag.
      if (is_bool($attributes_config)) {
        $key = $attributes_config ? '-attributes-any-' : '-attributes-none-';
        if (!isset($candidates[$tag_name][$key])) {
          // Sadly no plugin found for this tag + unspecific attribute.
          continue;
        }
        asort($candidates[$tag_name][$key]);
        $selected_plugin_id = array_keys($candidates[$tag_name][$key])[0];
        $selected_plugins[$selected_plugin_id][$key][$tag_name] = NULL;
        continue;
      }

      // Specific attribute restrictions for tag.
      foreach ($attributes_config as $attribute_name => $attribute_config) {
        if (!isset($candidates[$tag_name][$attribute_name])) {
          // Sadly no plugin found for this tag + attribute.
          continue;
        }
        if (!is_array($attribute_config)) {
          if (!isset($candidates[$tag_name][$attribute_name][$attribute_config])) {
            // Sadly no plugin found for this tag + attribute + config.
            continue;
          }
          asort($candidates[$tag_name][$attribute_name][$attribute_config]);
          $selected_plugin_id = array_keys($candidates[$tag_name][$attribute_name][$attribute_config])[0];
          $selected_plugins[$selected_plugin_id][$attribute_name][$tag_name] = $attribute_config;
          continue;
        }
        else {
          foreach ($attribute_config as $allowed_attribute_value => $allowed_attribute_value_config) {
            if (!isset($candidates[$tag_name][$attribute_name][$allowed_attribute_value][$allowed_attribute_value_config])) {
              // Sadly no plugin found for this tag + attr + value + config.
              continue;
            }
            asort($candidates[$tag_name][$attribute_name][$allowed_attribute_value][$allowed_attribute_value_config]);
            $selected_plugin_id = array_keys($candidates[$tag_name][$attribute_name][$allowed_attribute_value][$allowed_attribute_value_config])[0];
            $selected_plugins[$selected_plugin_id][$attribute_name][$tag_name][$allowed_attribute_value] = $allowed_attribute_value_config;
            continue;
          }
        }
      }

      // If we got to this point, no exact match was found. But selecting a
      // plugin to support the tag at all (when it is not yet supported) is
      // crucial to meet the user's expectations.
      // For example: when `<blockquote cite>` is needed, select at least the
      // plugin that can support `<blockquote>`, then only the `cite` attribute
      // needs to be made possible using the `SourceEditing` plugin.
      if (!in_array($tag_name, $already_supported_tags, TRUE) && isset($candidates[$tag_name]['-attributes-none-'])) {
        asort($candidates[$tag_name]['-attributes-none-']);
        $selected_plugin_id = array_keys($candidates[$tag_name]['-attributes-none-'])[0];
        $selected_plugins[$selected_plugin_id]['-attributes-none-'][$tag_name] = NULL;
      }
    }

    // The above selects all exact matches. It's possible the same plugin is
    // selected for multiple reasons: for supporting the tag at all, but also
    // for supporting more attributes on the tag. Whenever that scenario
    // occurs, keep only the "tag" reason, since that is the most relevant one
    // for the end user. Otherwise a single plugin being selected (and enabled)
    // could generate multiple messages, which would be confusing and
    // overwhelming for the user.
    // For example: when `<a href>` is needed, supporting `<a>` is more
    // relevant to be informed about as an end user than the plugin also being
    // enabled to support the `href` attribute.
    foreach ($selected_plugins as $selected_plugin_id => $reason) {
      if (count($reason) > 1 && isset($reason['-attributes-none-'])) {
        $selected_plugins[$selected_plugin_id] = array_intersect_key($reason, ['-attributes-none-' => TRUE]);
      }
    }

    return $selected_plugins;
  }

  /**
   * Adds CKEditor 5 toolbar items to match the format's HTML elements.
   *
   * @param \Drupal\filter\FilterFormatInterface $format
   *   The text format for which to compute smart default settings.
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor config entity to update.
   *
   * @return array|null
   *   NULL when nothing happened, otherwise an array with four values:
   *   1. a description (for use in a message) of which CKEditor 5 plugins were
   *      enabled to match the HTML tags allowed by the text format.
   *   2. a description (for use in a message) of which CKEditor 5 plugins were
   *      enabled to match the HTML attributes allowed by the text format.
   *   3. the unsupported elements, in an HTMLRestrictions value object.
   *   4. the list of enabled plugin labels.
   */
  private function addToolbarItemsToMatchHtmlElementsInFormat(FilterFormatInterface $format, EditorInterface $editor): ?array {
    $html_restrictions_needed_elements = $format->getHtmlRestrictions();
    if ($html_restrictions_needed_elements === FALSE) {
      return NULL;
    }

    $all_definitions = $this->pluginManager->getDefinitions();
    $enabled_definitions = $this->pluginManager->getEnabledDefinitions($editor);
    $disabled_definitions = array_diff_key($all_definitions, $enabled_definitions);
    $enabled_plugins = array_keys($enabled_definitions);
    $provided_elements = $this->pluginManager->getProvidedElements($enabled_plugins, $editor);
    $provided = new HTMLRestrictions($provided_elements);
    $needed = HTMLRestrictions::fromTextFormat($format);
    // Plugins only supporting <tag attr> cannot create the tag. For that, they
    // must support plain <tag> too. With this being the case, break down what
    // is needed based on what is currently provided.
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::getCreatableElements()
    // TRICKY: the HTMLRestrictions value object can only convey complete
    // restrictions: merging <foo> and <foo bar> results in just <foo bar>. The
    // list of already provided plain tags must hence be constructed separately.
    $provided_plain_tags = new HTMLRestrictions(
      $this->pluginManager->getProvidedElements($enabled_plugins, NULL, FALSE, TRUE)
    );

    // Determine the still needed plain tags, the still needed attributes, and
    // the union of both.
    $still_needed_plain_tags = $needed->extractPlainTagsSubset()->diff($provided_plain_tags);
    $still_needed_attributes = $needed->diff($provided)->diff($still_needed_plain_tags);
    $still_needed = $still_needed_plain_tags->merge($still_needed_attributes);

    if (!$still_needed->allowsNothing()) {
      // Select plugins for supporting the still needed plain tags.
      $plugin_candidates_plain_tags = self::getCandidates($provided_plain_tags, $still_needed_plain_tags, $disabled_definitions);
      $selected_plugins_plain_tags = self::selectCandidate($plugin_candidates_plain_tags, $still_needed_plain_tags, array_keys($provided_plain_tags->getAllowedElements()));

      // Select plugins for supporting the still needed attributes.
      $plugin_candidates_attributes = self::getCandidates($provided, $still_needed_attributes, $disabled_definitions);
      $selected_plugins_attributes = self::selectCandidate($plugin_candidates_attributes, $still_needed, array_keys($provided->getAllowedElements()));

      // Combine the selection.
      $selected_plugins = array_merge_recursive($selected_plugins_plain_tags, $selected_plugins_attributes);

      // If additional plugins need to be enabled to support attribute config,
      // loop through the list to enable the plugins and build a UI message that
      // will convey this plugin-enabling to the user.
      if (!empty($selected_plugins)) {
        $enabled_for_tags_message_content = '';
        $enabled_for_attributes_message_content = '';
        $editor_settings_to_update = $editor->getSettings();
        // Create new group for all the added toolbar items.
        $editor_settings_to_update['toolbar']['items'][] = '|';
        foreach ($selected_plugins as $plugin_id => $reason_why_enabled) {
          $plugin_definition = $this->pluginManager->getDefinition($plugin_id);
          $label = $plugin_definition->label();
          $plugins_enabled[] = $label;
          if ($plugin_definition->hasToolbarItems()) {
            [$net_new] = self::computeNetNewElementsForPlugin($provided, $still_needed, $plugin_definition);
            $editor_settings_to_update['toolbar']['items'] = array_merge($editor_settings_to_update['toolbar']['items'], array_keys($plugin_definition->getToolbarItems()));
            foreach ($reason_why_enabled as $attribute_name => $attribute_config) {
              // Plugin was selected for tag.
              if (in_array($attribute_name, ['-attributes-none-', '-attributes-any-'], TRUE)) {
                $tags = array_reduce(array_keys($net_new->getAllowedElements()), function ($carry, $item) {
                  return $carry . "<$item>";
                });
                $enabled_for_tags_message_content .= "$label (for tags: $tags) ";
                // This plugin does not add attributes: continue to next plugin.
                continue;
              }
              // Plugin was selected for attribute.
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

            // Fewer attributes are still needed.
            $still_needed = $still_needed->diff($net_new);
          }
        }
        $editor->setSettings($editor_settings_to_update);
        // Some plugins enabled, maybe some missing tags or attributes.
        return [
          substr($enabled_for_tags_message_content, 0, -1),
          substr($enabled_for_attributes_message_content, 0, -2),
          $still_needed,
          $plugins_enabled,
        ];
      }
      else {
        // No plugins enabled, maybe some missing tags or attributes.
        return [
          NULL,
          NULL,
          $still_needed,
          NULL,
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
      $default_plugin_configuration = $this->pluginManager->getPlugin($plugin_name, NULL)->defaultConfiguration();
      // Skip plugins with an empty default configuration, the plugin
      // configuration is most likely stored elsewhere. Also skip any plugin
      // that already has configuration data as default values are not needed.
      if ($default_plugin_configuration === [] || isset($settings['plugins'][$plugin_name])) {
        continue;
      }
      $update_settings = TRUE;
      $settings['plugins'][$plugin_name] = $default_plugin_configuration;
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
        $messages[MessengerInterface::TYPE_WARNING][] = $this->t('The CKEditor 5 plugin %button has a configurable subset of elements, but does not have a known upgrade path to configure that subset to match your text format. Hence it is now using its default configuration.', [
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
