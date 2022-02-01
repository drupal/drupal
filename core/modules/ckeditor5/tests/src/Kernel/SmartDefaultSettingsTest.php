<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\ckeditor5\HTMLRestrictionsUtilities;
use Drupal\Component\Utility\NestedArray;
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
   *
   * @dataProvider provider
   */
  public function test(string $format_id, array $filters_to_drop, array $expected_ckeditor5_settings, string $expected_superset, array $expected_fundamental_compatibility_violations, array $expected_messages, ?array $expected_post_filter_drop_fundamental_compatibility_violations = NULL): void {
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
    $typed_config = $this->typedConfig->createFromNameAndData(
      $updated_text_editor->getConfigDependencyName(),
      $updated_text_editor->toArray(),
    );
    $this->assertCount(0, $typed_config->validate());

    // If the text format has HTML restrictions, ensure that a strict superset
    // is allowed after switching to CKEditor 5.
    $html_restrictions = $text_format->getHtmlRestrictions();
    $allowed_tags = $html_restrictions['allowed'] ?? [];
    if ($allowed_tags) {
      unset($allowed_tags['*']);
      $enabled_plugins = array_keys($this->manager->getEnabledDefinitions($updated_text_editor));
      $updated_allowed_tags = $this->manager->getProvidedElements($enabled_plugins, $updated_text_editor);
      $unsupported_tags_attributes = HTMLRestrictionsUtilities::diffAllowedElements($allowed_tags, $updated_allowed_tags);
      $superset_tags_attributes = HTMLRestrictionsUtilities::diffAllowedElements($updated_allowed_tags, $allowed_tags);
      $this->assertSame($expected_superset, implode(' ', HTMLRestrictionsUtilities::toReadableElements($superset_tags_attributes)));
      $this->assertEmpty($unsupported_tags_attributes, "The following tags/attributes are not allowed in the updated text format:" . print_r($unsupported_tags_attributes, TRUE));

      // Update the text format like ckeditor5_form_filter_format_form_alter()
      // would.
      $updated_text_format = clone $text_format;
      $filter_html_config = $text_format->filters('filter_html')->getConfiguration();
      $filter_html_config['settings']['allowed_html'] = implode(' ', HTMLRestrictionsUtilities::toReadableElements($updated_allowed_tags));
      $updated_text_format->setFilterConfig('filter_html', $filter_html_config);
    }
    else {
      // No update.
      $updated_text_format = $text_format;
    }

    // The resulting pair should be valid.
    $this->assertSame([], $this->validatePairToViolationsArray($updated_text_editor, $updated_text_format, TRUE));

    foreach ($messages as $key => $message) {
      $messages[$key] = (string) $message;
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
            // The 'textPartLanguage' button added because <span> is allowed.
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<cite>',
              '<dl>',
              '<dt>',
              '<dd>',
              '<a hreflang>',
              '<blockquote cite>',
              '<ul type>',
              '<ol start type>',
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
          'ckeditor5_language' => [
            'language_list' => 'un',
          ],
        ],
      ],
      'expected_superset' => '<span lang dir>',
      'expected_fundamental_compatibility_violations' => [],
      'expected_messages' => [
        'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Code (for tags: &lt;code&gt;) Language (for tags: &lt;span&gt;)</em>.',
        'The following tags were permitted by this format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;.',
      ],
    ];

    yield "basic_html can be switched to CKEditor 5 without problems (3 upgrade messages)" => NestedArray::mergeDeep(
      $basic_html_test_case,
      [
        'expected_messages' => [
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
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
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;img data-caption&gt;.',
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
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;img data-align&gt;.',
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
          'ckeditor5_language' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_language'],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge(
        $basic_html_test_case['expected_messages'],
        ['This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h5 id&gt;.'],
      ),
    ];

    yield "basic_html_with_alignable_p can be switched to CKEditor 5 without problems, align buttons added automatically" => [
      'format_id' => 'basic_html_with_alignable_p',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
            // @todo Improve in https://www.drupal.org/project/drupal/issues/3259593
            [
              'alignment',
              'alignment:center',
              'alignment:justify',
            ]
          ),
        ],
        'plugins' => $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
      ],
      'expected_superset' => implode(' ', [
        // Note that aligning left and right is being added, on top of what the
        // original format allowed: center and justify.
        // @todo Improve in https://www.drupal.org/project/drupal/issues/3231328
        '<p class="text-align-left text-align-right">',
        // Note that aligning left/center/right/justify is possible on *all*
        // allowed block-level HTML5 tags.
        // @todo When https://www.drupal.org/project/ckeditor5/issues/3231328
        //   lands, only the center/justify classes will be added.
        // @todo When https://www.drupal.org/project/drupal/issues/3259367
        //   lands, none of the tags below should appear.
        '<h2 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h3 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h4 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h5 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h6 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<dl class="text-align-left text-align-center text-align-right text-align-justify">',
        '<dd class="text-align-left text-align-center text-align-right text-align-justify">',
        '<blockquote class="text-align-left text-align-center text-align-right text-align-justify">',
        '<ul class="text-align-left text-align-center text-align-right text-align-justify">',
        '<ol class="text-align-left text-align-center text-align-right text-align-justify">',
        $basic_html_test_case['expected_superset'],
      ]),
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge($basic_html_test_case['expected_messages'],

        [
          'The following plugins were enabled to support specific attributes that are allowed by this text format: <em class="placeholder">Alignment ( for tag: &lt;p&gt; to support: class with value(s):  text-align-center, text-align-justify), Align center ( for tag: &lt;p&gt; to support: class with value(s):  text-align-center), Justify ( for tag: &lt;p&gt; to support: class with value(s):  text-align-justify)</em>.',
          'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
        ]),
    ];

    yield "basic_html with media_embed added => <drupal-media> needed => supported through sourceEditing (3 upgrade messages)" => [
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
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<drupal-media data-align data-caption>'],
            ),
          ],
        ] + $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_messages' => array_merge($basic_html_test_case['expected_messages'], [
        "This format's HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin's <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;drupal-media data-align data-caption&gt;.",
      ]),
    ];

    yield "restricted_html can be switched to CKEditor 5 after dropping the two markup-creating filters (3 upgrade messages)" => [
      'format_id' => 'restricted_html',
      'filters_to_drop' => [
        'filter_autop' => TRUE,
        'filter_url' => TRUE,
      ],
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
              '<ol start type>',
              '<h2 id>',
              '<h3 id>',
              '<h4 id>',
              '<h5 id>',
              '<h6 id>',
            ],
          ],
        ],
      ],
      'expected_superset' => '<br> <p>',
      'expected_fundamental_compatibility_violations' => [
        '' => [
          0 => 'CKEditor 5 only works with HTML-based text formats. The "<em class="placeholder">Convert line breaks into HTML (i.e. &lt;code&gt;&amp;lt;br&amp;gt;&lt;/code&gt; and &lt;code&gt;&amp;lt;p&amp;gt;&lt;/code&gt;)</em>" (<em class="placeholder">filter_autop</em>) filter implies this text format is not HTML anymore.',
          1 => 'CKEditor 5 only works with HTML-based text formats. The "<em class="placeholder">Convert URLs into links</em>" (<em class="placeholder">filter_url</em>) filter implies this text format is not HTML anymore.',
        ],
      ],
      'expected_messages' => [
        'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;)</em>.',
        'The following tags were permitted by this format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;.',
        'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
      ],
      'expected_post_filter_drop_fundamental_compatibility_violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
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
              '<ol start type="1 A I">',
              '<h2 id="jump-*">',
              '<h3 id>',
              '<h4 id>',
              '<h5 id>',
              '<h6 id>',
            ],
          ],
        ],
      ],
      'expected_superset' => '<br> <p>',
      'expected_fundamental_compatibility_violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
      'expected_messages' => [
        'The following plugins were enabled to support tags that are allowed by this text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;)</em>.',
        'The following tags were permitted by this format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;.',
        'This format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol start type=&quot;1 A I&quot;&gt; &lt;h2 id=&quot;jump-*&quot;&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;.',
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
        'The CKEditor 4 button <em class="placeholder">Llama</em> does not have a known upgrade path. If it allowed editing markup, then you can do so now through the Source Editing functionality.',
        'The <em class="placeholder">llama_contextual_and_button</em> plugin settings do not have a known upgrade path.',
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
  }

}
