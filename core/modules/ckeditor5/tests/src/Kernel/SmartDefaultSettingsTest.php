<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Drupal\ckeditor5\SmartDefaultSettings::computeSmartDefaultSettings()
 * @group ckeditor5
 * @internal
 */
class SmartDefaultSettingsTest extends KernelTestBase {

  use SchemaCheckTestTrait;
  use CKEditor5ValidationTestTrait;

  /**
   * The manager for "CKEditor 5 plugin" plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

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
    'ckeditor_test',
    'ckeditor5',
    'editor',
    'filter',
    'user',
    // For being able to test media_embed + Media button in CKE4/CKE5.
    'media',
    'media_library',
    'views',
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3263384
    'ckeditor5_plugin_conditions_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->typedConfig = $this->container->get('config.typed');
    $this->smartDefaultSettings = $this->container->get('ckeditor5.smart_default_settings');

    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.full_html.yml')
    )
      ->setSyncing(TRUE)
      ->save();
    Editor::create(
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.full_html.yml')
    )->save();

    $basic_html_format = Yaml::parseFile('core/profiles/standard/config/install/filter.format.basic_html.yml');
    FilterFormat::create($basic_html_format)->save();
    Editor::create(
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.restricted_html.yml')
    )->save();

    $allowed_html_parents = ['filters', 'filter_html', 'settings', 'allowed_html'];
    $current_value = NestedArray::getValue($basic_html_format, $allowed_html_parents);
    $new_value = str_replace(['<h4 id> ', '<h6 id> '], '', $current_value);
    $basic_html_format_without_h4_h6 = $basic_html_format;
    $basic_html_format_without_h4_h6['name'] .= ' (without H4 and H6)';
    $basic_html_format_without_h4_h6['format'] = 'basic_html_without_h4_h6';
    NestedArray::setValue($basic_html_format_without_h4_h6, $allowed_html_parents, $new_value);
    FilterFormat::create($basic_html_format_without_h4_h6)->save();
    Editor::create(
      ['format' => 'basic_html_without_h4_h6']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    $new_value = str_replace(['<h2 id> ', '<h3 id> ', '<h4 id> ', '<h5 id> ', '<h6 id> '], '', $current_value);
    $basic_html_format_without_headings = $basic_html_format;
    $basic_html_format_without_headings['name'] .= ' (without H*)';
    $basic_html_format_without_headings['format'] = 'basic_html_without_headings';
    NestedArray::setValue($basic_html_format_without_headings, $allowed_html_parents, $new_value);
    FilterFormat::create($basic_html_format_without_headings)->save();
    Editor::create(
      ['format' => 'basic_html_without_headings']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_pre = $basic_html_format;
    $basic_html_format_with_pre['name'] .= ' (with <pre>)';
    $basic_html_format_with_pre['format'] = 'basic_html_with_pre';
    NestedArray::setValue($basic_html_format_with_pre, $allowed_html_parents, $current_value . ' <pre>');
    FilterFormat::create($basic_html_format_with_pre)->save();
    Editor::create(
      ['format' => 'basic_html_with_pre']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_h1 = $basic_html_format;
    $basic_html_format_with_h1['name'] .= ' (with <h1>)';
    $basic_html_format_with_h1['format'] = 'basic_html_with_h1';
    NestedArray::setValue($basic_html_format_with_h1, $allowed_html_parents, $current_value . ' <h1>');
    FilterFormat::create($basic_html_format_with_h1)->save();
    Editor::create(
      ['format' => 'basic_html_with_h1']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    $new_value = str_replace('<p>', '<p class="text-align-center text-align-justify">', $current_value);
    $basic_html_format_with_alignable_p = $basic_html_format;
    $basic_html_format_with_alignable_p['name'] .= ' (with alignable paragraph support)';
    $basic_html_format_with_alignable_p['format'] = 'basic_html_with_alignable_p';
    NestedArray::setValue($basic_html_format_with_alignable_p, $allowed_html_parents, $new_value);
    FilterFormat::create($basic_html_format_with_alignable_p)->save();
    Editor::create(
      ['format' => 'basic_html_with_alignable_p']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_media_embed = $basic_html_format;
    $basic_html_format_with_media_embed['name'] .= ' (with Media Embed support)';
    $basic_html_format_with_media_embed['format'] = 'basic_html_with_media_embed';
    // Add media_embed filter, update filter_html filter settings.
    $basic_html_format_with_media_embed['filters']['media_embed'] = ['status' => TRUE];
    $new_value = $current_value . ' <drupal-media data-entity-type data-entity-uuid data-align data-caption alt>';
    NestedArray::setValue($basic_html_format_with_media_embed, $allowed_html_parents, $new_value);
    FilterFormat::create($basic_html_format_with_media_embed)->save();
    $basic_html_editor_with_media_embed = Editor::create(
      ['format' => 'basic_html_with_media_embed']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    );
    $settings = $basic_html_editor_with_media_embed->getSettings();
    // Add "insert media from library" button to CKEditor 4 configuration, the
    // pre-existing toolbar item group labeled "Media".
    $settings['toolbar']['rows'][0][3]['items'][] = 'DrupalMediaLibrary';
    $basic_html_editor_with_media_embed->setSettings($settings);
    $basic_html_editor_with_media_embed->save();

    $basic_html_format_with_media_embed_view_mode_invalid = $basic_html_format_with_media_embed;
    $basic_html_format_with_media_embed_view_mode_invalid['name'] = ' (with Media Embed support, view mode enabled but no view modes configured)';
    $basic_html_format_with_media_embed_view_mode_invalid['format'] = 'basic_html_with_media_embed_view_mode_enabled_no_view_modes_configured';
    $current_value_media_embed = NestedArray::getValue($basic_html_format_with_media_embed, $allowed_html_parents);
    $new_value = str_replace('<drupal-media data-entity-type data-entity-uuid data-align data-caption alt>', '<drupal-media data-entity-type data-entity-uuid data-align data-caption alt data-view-mode>', $current_value_media_embed);
    NestedArray::setValue($basic_html_format_with_media_embed_view_mode_invalid, $allowed_html_parents, $new_value);
    FilterFormat::create($basic_html_format_with_media_embed_view_mode_invalid)->save();
    $basic_html_editor_with_media_embed_view_mode_enabled_no_view_modes_configured = Editor::create(
      ['format' => 'basic_html_with_media_embed_view_mode_enabled_no_view_modes_configured']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    );
    $settings = $basic_html_editor_with_media_embed_view_mode_enabled_no_view_modes_configured->getSettings();
    // Add "insert media from library" button to CKEditor 4 configuration, the
    // pre-existing toolbar item group labeled "Media".
    $settings['toolbar']['rows'][0][3]['items'][] = 'DrupalMediaLibrary';
    $basic_html_editor_with_media_embed_view_mode_enabled_no_view_modes_configured->setSettings($settings);
    $basic_html_editor_with_media_embed_view_mode_enabled_no_view_modes_configured->save();

    $new_value = str_replace('<img src alt height width data-entity-type data-entity-uuid data-align data-caption>', '<img src alt height width data-*>', $current_value);
    $basic_html_format_with_any_data_attr = $basic_html_format;
    $basic_html_format_with_any_data_attr['name'] .= ' (with any data-* attribute on images)';
    $basic_html_format_with_any_data_attr['format'] = 'basic_html_with_any_data_attr';
    NestedArray::setValue($basic_html_format_with_any_data_attr, $allowed_html_parents, $new_value);
    FilterFormat::create($basic_html_format_with_any_data_attr)->save();
    Editor::create(
      ['format' => 'basic_html_with_any_data_attr']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured = $basic_html_format_with_media_embed_view_mode_invalid;
    $basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured['name'] = ' (with Media Embed support, view mode enabled and two view modes configured )';
    $basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured['format'] = 'basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured';
    FilterFormat::create($basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured)->save();
    $basic_html_editor_with_media_embed_view_mode_enabled_two_view_modes_configured = Editor::create(
      ['format' => 'basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured']
      +
      Yaml::parseFile('core/profiles/standard/config/install/editor.editor.basic_html.yml')
    );
    $settings = $basic_html_editor_with_media_embed_view_mode_enabled_two_view_modes_configured->getSettings();
    // Add "insert media from library" button to CKEditor 4 configuration, the
    // pre-existing toolbar item group labeled "Media".
    $settings['toolbar']['rows'][0][3]['items'][] = 'DrupalMediaLibrary';
    $basic_html_editor_with_media_embed_view_mode_enabled_two_view_modes_configured->setSettings($settings);
    $basic_html_editor_with_media_embed_view_mode_enabled_two_view_modes_configured->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 1',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_2',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 2',
    ])->save();
    $filter_format = FilterFormat::load('basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured');
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          'view_mode_2' => 'view_mode_2',
        ],
      ],
    ])->save();

    $filter_plugin_manager = $this->container->get('plugin.manager.filter');
    FilterFormat::create([
      'format' => 'filter_only__filter_html',
      'name' => 'Only the "filter_html" filter and its default settings',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => $filter_plugin_manager->getDefinition('filter_html')['settings'],
        ],
      ],
    ])->save();

    FilterFormat::create([
      'format' => 'cke4_plugins_with_settings',
      'name' => 'All CKEditor 4 core plugins with settings',
    ])->save();
    Editor::create([
      'format' => 'cke4_plugins_with_settings',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Buttons with settings',
                'items' => [
                  'Language',
                  'Styles',
                ],
              ],
              [
                'name' => 'Button without upgrade path',
                'items' => [
                  'Llama',
                ],
              ],
            ],
          ],
        ],
        'plugins' => [
          'language' => [
            'language_list' => 'all',
          ],
          'stylescombo' => [
            'styles' => "p.callout|Callout\r\nblockquote.interesting|Interesting quote",
          ],
          // Plugin setting without upgrade path.
          'llama_contextual_and_button' => [
            'ultra_llama_mode' => TRUE,
          ],
        ],
      ],
    ])->save();

    FilterFormat::create([
      'format' => 'cke4_plugins_with_settings_for_disabled_plugins',
      'name' => 'All CKEditor 4 core plugins with settings for disabled plugins',
    ])->save();
    Editor::create([
      'format' => 'cke4_plugins_with_settings_for_disabled_plugins',
      'editor' => 'ckeditor',
      'settings' => [
        // Empty toolbar.
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Only buttons without settings',
                'items' => [
                  'Bold',
                ],
              ],
            ],
          ],
        ],
        // Same plugin settings as `cke4_plugins_with_settings`.
        'plugins' => Editor::load('cke4_plugins_with_settings')->getSettings()['plugins'],
      ],
    ])->save();

    FilterFormat::create([
      'format' => 'cke4_contrib_plugins_now_in_core',
      'name' => 'All CKEditor 4 contrib plugins now in core',
    ])->save();
    Editor::create([
      'format' => 'cke4_contrib_plugins_now_in_core',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Contributed modules providing buttons without settings',
                'items' => [
                  // @see https://www.drupal.org/project/codetag
                  'Code',
                ],
              ],
            ],
          ],
        ],
        'plugins' => [],
      ],
    ])->save();
  }

  /**
   * Tests the CKEditor 5 default settings conversion.
   *
   * @param string $format_id
   *   The existing text format/editor pair to switch to CKEditor 5.
   * @param array $filters_to_drop
   *   An array of filter IDs to drop as the keys and either TRUE (fundamental
   *   compatibility error from CKEditor 5 expected) or FALSE (if optional to
   *   drop).
   * @param array $expected_ckeditor5_settings
   *   The CKEditor 5 settings to test.
   * @param string $expected_superset
   *   The default settings conversion may generate a superset of the original
   *   HTML restrictions. This lists the additional elements and attributes.
   * @param array $expected_fundamental_compatibility_violations
   *   All expected fundamental compatibility violations for the given text
   *   format.
   * @param string[] $expected_messages
   *   The expected messages associated with the computed settings.
   * @param array|null $expected_post_filter_drop_fundamental_compatibility_violations
   *   All expected fundamental compatibility violations for the given text
   *   format, after dropping filters specified in $filters_to_drop.
   * @param array|null $expected_post_update_text_editor_violations
   *   All expected media and filter settings violations for the given text
   *   format.
   *
   * @dataProvider provider
   */
  public function test(string $format_id, array $filters_to_drop, array $expected_ckeditor5_settings, string $expected_superset, array $expected_fundamental_compatibility_violations, array $expected_messages, ?array $expected_post_filter_drop_fundamental_compatibility_violations = NULL, ?array $expected_post_update_text_editor_violations = NULL): void {
    $text_format = FilterFormat::load($format_id);
    $text_editor = Editor::load($format_id);

    // Check the pre-CKE5 switch validation errors in case of a minimal (empty)
    // CKEditor 5 text editor config entity, to allow us to detect fundamental
    // compatibility problems, such as incompatible filters.
    $minimal_valid_cke5_text_editor = Editor::create([
      'format' => $format_id,
      'editor' => 'ckeditor5',
      'settings' => ['toolbar' => ['items' => []]],
    ]);
    $pre_ck5_validation_errors = $this->validatePairToViolationsArray($minimal_valid_cke5_text_editor, $text_format, FALSE);
    $this->assertSame($expected_fundamental_compatibility_violations, $pre_ck5_validation_errors);

    if (!empty($filters_to_drop)) {
      foreach ($filters_to_drop as $filter_name => $is_fundamentally_incompatible) {
        // Assert if it should appear in the pre-CKE5 switch validation errors.
        $this->assertSame($is_fundamentally_incompatible, mb_strpos(implode("\n\n", $pre_ck5_validation_errors[''] ?? []), $filter_name) !== FALSE);
        $text_format->setFilterConfig($filter_name, [
          'status' => FALSE,
        ]);
      }

      // If filters were dropped because of a fundamental compatibility problem,
      // validate the text format + minimal CKEditor 5 text editor config again
      // after dropping those filters from the text format. This allows us to be
      // confident that we have caught all fundamental compatibility problems.
      if (!empty(array_filter($filters_to_drop))) {
        $post_filter_drop_validation_errors = $this->validatePairToViolationsArray($minimal_valid_cke5_text_editor, $text_format, FALSE);
        $this->assertSame($expected_post_filter_drop_fundamental_compatibility_violations, $post_filter_drop_validation_errors);
      }
    }

    [$updated_text_editor, $messages] = $this->smartDefaultSettings->computeSmartDefaultSettings($text_editor, $text_format);

    // Ensure that the result of ::computeSmartDefaultSettings() always complies
    // with the config schema.
    $this->assertConfigSchema(
      $this->typedConfig,
      $updated_text_editor->getConfigDependencyName(),
      $updated_text_editor->toArray()
    );

    // We should now have the expected data in the Editor config entity.
    $this->assertSame('ckeditor5', $updated_text_editor->getEditor());
    $this->assertSame($expected_ckeditor5_settings, $updated_text_editor->getSettings());

    // If this text format already had a text editor, ensure that the settings
    // do not match the original settings, but the image upload settings should
    // not have been changed.
    if ($text_editor !== NULL) {
      $this->assertNotSame($text_editor->getSettings(), $updated_text_editor->getSettings());
      $this->assertSame($text_editor->getImageUploadSettings(), $updated_text_editor->getImageUploadSettings());
    }

    // The resulting Editor config entity should be valid.
    $violations = $this->validatePairToViolationsArray($updated_text_editor, $text_format, FALSE);
    // At this point, the fundamental compatibility errors do not matter, they
    // have been checked above; whatever remains is expected.
    if (isset($violations[''])) {
      unset($violations['']);
    }
    $this->assertSame([], $violations);

    // If the text format has HTML restrictions, ensure that a strict superset
    // is allowed after switching to CKEditor 5.
    $html_restrictions = $text_format->getHtmlRestrictions();
    if (is_array($html_restrictions) && array_key_exists('allowed', $html_restrictions)) {
      $allowed_tags = HTMLRestrictions::fromTextFormat($text_format);
      $enabled_plugins = array_keys($this->manager->getEnabledDefinitions($updated_text_editor));
      $updated_allowed_tags = new HTMLRestrictions($this->manager->getProvidedElements($enabled_plugins, $updated_text_editor));
      $unsupported_tags_attributes = $allowed_tags->diff($updated_allowed_tags);
      $superset_tags_attributes = $updated_allowed_tags->diff($allowed_tags);
      $this->assertSame($expected_superset, $superset_tags_attributes->toFilterHtmlAllowedTagsString());
      $this->assertTrue($unsupported_tags_attributes->allowsNothing(), "The following tags/attributes are not allowed in the updated text format:" . implode(' ', $unsupported_tags_attributes->toCKEditor5ElementsArray()));

      // Update the text format like ckeditor5_form_filter_format_form_alter()
      // would.
      $updated_text_format = clone $text_format;
      $filter_html_config = $text_format->filters('filter_html')->getConfiguration();
      $filter_html_config['settings']['allowed_html'] = $updated_allowed_tags->toFilterHtmlAllowedTagsString();
      $updated_text_format->setFilterConfig('filter_html', $filter_html_config);
    }
    else {
      // No update.
      $updated_text_format = $text_format;
    }

    $updated_validation_errors = $this->validatePairToViolationsArray($updated_text_editor, $updated_text_format, TRUE);
    if (is_null($expected_post_update_text_editor_violations)) {
      // If a violation is not expected, it should be compared against an empty array.
      $this->assertSame([], $updated_validation_errors);
    }
    else {
      $this->assertSame($expected_post_update_text_editor_violations, $updated_validation_errors);
    }

    // Transforms TranslatableMarkup objects to string.
    foreach ($messages as $type => $messages_per_type) {
      foreach ($messages_per_type as $key => $message) {
        $messages[$type][$key] = (string) $message;
      }
    }

    $this->assertSame($expected_messages, $messages);
  }

  /**
   * Data provider.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public function provider() {
    $basic_html_test_case = [
      'format_id' => 'basic_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Items based on toolbar items from prior config.
            'bold',
            'italic',
            '|',
            'link',
            '|',
            'bulletedList',
            'numberedList',
            '|',
            'blockQuote',
            'uploadImage',
            '|',
            'heading',
            '|',
            'sourceEditing',
            // Items added based on "allowed tags" config.
            '|',
            // The 'code' button added because <code> is allowed.
            'code',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<cite>',
              '<dl>',
              '<dt>',
              '<dd>',
              '<span>',
              '<a hreflang>',
              '<blockquote cite>',
              '<ul type>',
              '<ol type>',
              '<h2 id>',
              '<h3 id>',
              '<h4 id>',
              '<h5 id>',
              '<h6 id>',
            ],
          ],
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_imageResize' => [
            'allow_resize' => TRUE,
          ],
          'ckeditor5_list' => [
            'reversed' => FALSE,
            'startIndex' => TRUE,
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [
        'status' => [
          'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Code (for tags: &lt;code&gt;)</em>.',
          'The following tags were permitted by this format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;.',
        ],
      ],
    ];

    yield "basic_html can be switched to CKEditor 5 without problems (3 upgrade messages)" => NestedArray::mergeDeep(
      $basic_html_test_case,
      [
        'expected_messages' => [
          'status' => [
            'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
          ],
        ],
      ]
    );

    yield "basic_html with filter_caption removed => disallows <img data-caption> => supported through sourceEditing (3 upgrade messages)" => NestedArray::mergeDeep(
      $basic_html_test_case,
      [
        'filters_to_drop' => [
          'filter_caption' => FALSE,
        ],
        'expected_ckeditor5_settings' => [
          'plugins' => [
            'ckeditor5_sourceEditing' => [
              'allowed_tags' => [
                '<img data-caption>',
              ],
            ],
          ],
        ],
        'expected_messages' => [
          'status' => [
            'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;img data-caption&gt;.',
          ],
        ],
      ]);

    yield "basic_html with filter_align removed => disallows <img data-align> => supported through sourceEditing (3 upgrade messages) " => NestedArray::mergeDeep(
      $basic_html_test_case,
      [
        'filters_to_drop' => [
          'filter_align' => FALSE,
        ],
        'expected_ckeditor5_settings' => [
          'plugins' => [
            'ckeditor5_sourceEditing' => [
              'allowed_tags' => [
                '<img data-align>',
              ],
            ],
          ],
        ],
        'expected_messages' => [
          'status' => [
            'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;img data-align&gt;.',
          ],
        ],
      ]);

    yield "basic_html_without_h4_h6 can be switched to CKEditor 5 without problems, heading configuration computed automatically" => [
      'format_id' => 'basic_html_without_h4_h6',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_values(array_diff(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<h4 id>', '<h6 id>'],
            )),
          ],
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading5',
            ],
          ],
          'ckeditor5_imageResize' => ['allow_resize' => TRUE],
          'ckeditor5_list' => ['reversed' => FALSE, 'startIndex' => TRUE],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h5 id&gt;.',
        ],
      ]),
    ];

    yield "basic_html_with_h1 can be switched to CKEditor 5 without problems, heading configuration computed automatically" => [
      'format_id' => 'basic_html_with_h1',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
          ],
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading1',
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_imageResize' => ['allow_resize' => TRUE],
          'ckeditor5_list' => ['reversed' => FALSE, 'startIndex' => TRUE],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
        ],
      ]),
    ];

    yield "basic_html_without_headings can be switched to CKEditor 5 without problems, heading configuration computed automatically" => [
      'format_id' => 'basic_html_without_headings',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 0, 10),
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 12),
          ),
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_values(array_diff(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<h2 id>', '<h3 id>', '<h4 id>', '<h5 id>', '<h6 id>'],
            )),
          ],
          'ckeditor5_imageResize' => ['allow_resize' => TRUE],
          'ckeditor5_list' => ['reversed' => FALSE, 'startIndex' => TRUE],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt;.',
        ],
      ]),
    ];

    yield "basic_html_with_pre can be switched to CKEditor 5 without problems, heading configuration computed automatically" => [
      'format_id' => 'basic_html_with_pre',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
            ['codeBlock'],
          ),
        ],
        'plugins' => $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
      ],
      'expected_superset' => '<code class="language-*">',
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => [
        'status' => [
          'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Code (for tags: &lt;code&gt;) Code Block (for tags: &lt;pre&gt;)</em>.',
          $basic_html_test_case['expected_messages']['status'][1],
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
        ],
      ],
    ];

    yield "basic_html_with_alignable_p can be switched to CKEditor 5 without problems, align buttons added automatically" => [
      'format_id' => 'basic_html_with_alignable_p',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
            ['alignment'],
          ),
        ],
        'plugins' => array_merge(
          array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins'], 0, 1),
          [
            'ckeditor5_alignment' => [
              'enabled_alignments' => ['center', 'justify'],
            ],
          ],
          array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins'], 1),
        ),
      ],
      'expected_superset' => implode(' ', [
        // Note that aligning left and right is being added, on top of what the
        // original format allowed: center and justify.
        // Note that aligning left/center/right/justify is possible on *all*
        // allowed CKEditor 5 `$block` text container tags.
        // @todo When https://www.drupal.org/project/drupal/issues/3259367
        //   lands, none of the tags below should appear.
        '<h2 class="text-align-center text-align-justify">',
        '<h3 class="text-align-center text-align-justify">',
        '<h4 class="text-align-center text-align-justify">',
        '<h5 class="text-align-center text-align-justify">',
        '<h6 class="text-align-center text-align-justify">',
      ]),
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'The following plugins were enabled to support specific attributes that are allowed by this text format: <em class="placeholder">Alignment ( for tag: &lt;p&gt; to support: class with value(s):  text-align-center, text-align-justify)</em>.',
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
        ],
      ]),
    ];

    yield "basic_html with media_embed added (3 upgrade messages)" => [
      'format_id' => 'basic_html_with_media_embed',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 0, 10),
            ['drupalMedia'],
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 10),
          ),
        ],
        'plugins' => array_merge($basic_html_test_case['expected_ckeditor5_settings']['plugins'], ['media_media' => ['allow_view_mode_override' => FALSE]]),
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          "This format's HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.",
        ],
      ]),
    ];

    yield "basic_html with media_embed added with data-view-mode allowed but no view modes configured (3 upgrade messages, 1 violation)" => [
      'format_id' => 'basic_html_with_media_embed_view_mode_enabled_no_view_modes_configured',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 0, 10),
            ['drupalMedia'],
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 10),
          ),
        ],
        'plugins' => array_merge($basic_html_test_case['expected_ckeditor5_settings']['plugins'], ['media_media' => ['allow_view_mode_override' => TRUE]]),
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          "This format's HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.",
        ],
      ]),
      'expected_post_filter_drop_fundamental_compatibility_violations' => [],
      'expected_post_update_text_editor_violations' => [
        '' => 'The CKEditor 5 "<em class="placeholder">Media</em>" plugin\'s "<em class="placeholder">Allow the user to override the default view mode</em>" setting should be in sync with the "<em class="placeholder">Embed media</em>" filter\'s "<em class="placeholder">View modes selectable in the &quot;Edit media&quot; dialog</em>" setting: when checked, two or more view modes must be allowed by the filter.',
      ],
    ];

    yield "basic_html with media_embed added with data-view-mode allowed and 2 view modes configured (3 upgrade messages)" => [
      'format_id' => 'basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 0, 10),
            ['drupalMedia'],
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 10),
          ),
        ],
        'plugins' => array_merge($basic_html_test_case['expected_ckeditor5_settings']['plugins'], ['media_media' => ['allow_view_mode_override' => TRUE]]),
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          "This format's HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.",
        ],
      ]),
      'expected_post_filter_drop_fundamental_compatibility_violations' => [],
      'expected_post_update_text_editor_violations' => [],
    ];

    yield "basic_html_with_any_data_attr can be switched to CKEditor 5 without problems (3 upgrade messages)" => [
      'format_id' => 'basic_html_with_any_data_attr',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<img data-*>'],
            ),
          ],
        ] + $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;img data-*&gt;.',
        ],
      ]),
    ];

    yield "restricted_html can be switched to CKEditor 5 after dropping the two markup-creating filters (3 upgrade messages)" => [
      'format_id' => 'restricted_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Items originating from CKEditor5::getDefaultSettings().
            'heading',
            'bold',
            'italic',
            // Items added based on "allowed tags" config.
            '|',
            // Because '<a>' is in allowed_html.
            'link',
            // Because '<blockquote cite>' is in allowed_html.
            'blockQuote',
            // Because '<code>' is in allowed_html.
            'code',
            // Because '<ul>' is in allowed_html.
            'bulletedList',
            // Because '<ol>' is in allowed_html.
            'numberedList',
            // Because additional tags need to be allowed to achieve a superset.
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<cite>',
              '<dl>',
              '<dt>',
              '<dd>',
              '<a hreflang>',
              '<blockquote cite>',
              '<ul type>',
              '<ol type>',
              '<h2 id>',
              '<h3 id>',
              '<h4 id>',
              '<h5 id>',
              '<h6 id>',
            ],
          ],
          'ckeditor5_list' => [
            'reversed' => FALSE,
            'startIndex' => TRUE,
          ],
        ],
      ],
      'expected_superset' => '<br> <p>',
      'expected_fundamental_compatibility_violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
      'expected_messages' => [
        'status' => [
          'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;)</em>.',
          'The following tags were permitted by this format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;.',
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
          'The following tag(s) were added to <em>Limit allowed HTML tags and correct faulty HTML</em>, because they are needed to provide fundamental CKEditor 5 functionality : &lt;br&gt; &lt;p&gt;.',
        ],
      ],
      'expected_post_filter_drop_fundamental_compatibility_violations' => [],
    ];

    yield "full_html can be switched to CKEditor 5 (no upgrade messages)" => [
      'format_id' => 'full_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'bold',
            'italic',
            'strikethrough',
            'superscript',
            'subscript',
            'removeFormat',
            '|',
            'link',
            '|',
            'bulletedList',
            'numberedList',
            '|',
            'blockQuote',
            'uploadImage',
            'insertTable',
            'horizontalLine',
            '|',
            'heading',
            'codeBlock',
            '|',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_imageResize' => [
            'allow_resize' => TRUE,
          ],
          'ckeditor5_list' => [
            'reversed' => TRUE,
            'startIndex' => TRUE,
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [],
    ];

    yield "filter_only__filter_html can be switched to CKEditor 5 without problems (3 upgrade messages)" => [
      'format_id' => 'filter_only__filter_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Default toolbar items.
            'heading',
            'bold',
            'italic',
            '|',
            // Items added based on filter_html's "allowed tags" config.
            'link',
            'blockQuote',
            'code',
            'bulletedList',
            'numberedList',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<cite>',
              '<dl>',
              '<dt>',
              '<dd>',
              '<a hreflang>',
              '<blockquote cite>',
              '<ul type>',
              '<ol type="1 A I">',
              '<h2 id="jump-*">',
              '<h3 id>',
              '<h4 id>',
              '<h5 id>',
              '<h6 id>',
            ],
          ],
          'ckeditor5_list' => [
            'reversed' => FALSE,
            'startIndex' => TRUE,
          ],
        ],
      ],
      'expected_superset' => '<br> <p>',
      'expected_fundamental_compatibility_violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
      'expected_messages' => [
        'status' => [
          'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;)</em>.',
          'The following tags were permitted by this format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;.',
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type=&quot;1 A I&quot;&gt; &lt;h2 id=&quot;jump-*&quot;&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
          'The following tag(s) were added to <em>Limit allowed HTML tags and correct faulty HTML</em>, because they are needed to provide fundamental CKEditor 5 functionality : &lt;br&gt; &lt;p&gt;.',
        ],
      ],
    ];

    yield "cke4_plugins_with_settings can be switched to CKEditor 5 without problems, settings are upgraded too" => [
      'format_id' => 'cke4_plugins_with_settings',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'all',
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [
        'warning' => [
          'The CKEditor 4 button <em class="placeholder">Llama</em> does not have a known upgrade path. If it allowed editing markup, then you can do so now through the Source Editing functionality.',
          'The <em class="placeholder">llama_contextual_and_button</em> plugin settings do not have a known upgrade path.',
        ],
      ],
    ];

    yield "cke4_plugins_with_settings_for_disabled_plugins can be switched to CKEditor 5 without problems; irrelevant settings are dropped" => [
      'format_id' => 'cke4_plugins_with_settings_for_disabled_plugins',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [],
    ];

    yield "cke4_contrib_plugins_now_in_core can be switched to CKEditor 5 without problems" => [
      'format_id' => 'cke4_contrib_plugins_now_in_core',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'code',
          ],
        ],
        'plugins' => [],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [],
    ];
  }

}
