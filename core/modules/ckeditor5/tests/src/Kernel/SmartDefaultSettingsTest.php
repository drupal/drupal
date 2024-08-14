<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel;

// cspell:ignore arta codesnippet

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Drupal\ckeditor5\SmartDefaultSettings::computeSmartDefaultSettings
 * @group ckeditor5
 * @internal
 */
class SmartDefaultSettingsTest extends KernelTestBase {

  use SchemaCheckTestTrait;
  use CKEditor5ValidationTestTrait;

  /**
   * Exempt from strict schema checking, because using CKEditor 4.
   *
   * The updated Text Format & Text Editors are explicitly checked.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

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
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'editor',
    'filter',
    'user',
    // For being able to test media_embed + Media button in CKE5.
    'media',
    'media_library',
    'views',
    'dblog',
    'help',
    'editor_test',
    'ckeditor_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->typedConfig = $this->container->get('config.typed');
    $this->smartDefaultSettings = $this->container->get('ckeditor5.smart_default_settings');
    $this->database = $this->container->get('database');

    $this->installSchema('dblog', ['watchdog']);

    FilterFormat::create([
      'format' => 'minimal_ckeditor_wrong_allowed_html',
      'name' => 'Most basic HTML, but with allowed_html misconfigured',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            // Misconfiguration aspects:
            // 1. `<a>`, not `<a href>`, while `DrupalLink` is enabled
            // 2. `<p style>` even though `style` is globally disallowed by
            //    filter_html
            // 3. `<a onclick>` even though `on*` is globally disallowed by
            //    filter_html
            'allowed_html' => '<p style> <br> <a onclick>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'minimal_ckeditor_wrong_allowed_html',
      'editor' => 'ckeditor',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Basic Formatting',
                'items' => [
                  'DrupalLink',
                ],
              ],
            ],
          ],
        ],
        'plugins' => [],
      ],
    ])->save();

    FilterFormat::create(
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/filter.format.full_html.yml')
    )->save();

    Editor::create(
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.full_html.yml')
    )->save();

    $basic_html_format = Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/filter.format.basic_html.yml');
    FilterFormat::create($basic_html_format)->save();
    Editor::create(
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
    )->save();

    FilterFormat::create(
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/filter.format.restricted_html.yml')
    )->save();

    $basic_html_format_without_image_uploads = $basic_html_format;
    $basic_html_format_without_image_uploads['name'] .= ' (without image uploads)';
    $basic_html_format_without_image_uploads['format'] = 'basic_html_without_image_uploads';
    FilterFormat::create($basic_html_format_without_image_uploads)->save();
    Editor::create(
      ['format' => 'basic_html_without_image_uploads']
      +
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
    )->setImageUploadSettings(['status' => FALSE])->save();

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
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
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
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_pre = $basic_html_format;
    $basic_html_format_with_pre['name'] .= ' (with <pre>)';
    $basic_html_format_with_pre['format'] = 'basic_html_with_pre';
    NestedArray::setValue($basic_html_format_with_pre, $allowed_html_parents, $current_value . ' <pre>');
    FilterFormat::create($basic_html_format_with_pre)->save();
    Editor::create(
      ['format' => 'basic_html_with_pre']
      +
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_h1 = $basic_html_format;
    $basic_html_format_with_h1['name'] .= ' (with <h1>)';
    $basic_html_format_with_h1['format'] = 'basic_html_with_h1';
    NestedArray::setValue($basic_html_format_with_h1, $allowed_html_parents, $current_value . ' <h1>');
    FilterFormat::create($basic_html_format_with_h1)->save();
    Editor::create(
      ['format' => 'basic_html_with_h1']
      +
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
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
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
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
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
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
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
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
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
    )->save();

    $basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured = $basic_html_format_with_media_embed_view_mode_invalid;
    $basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured['name'] = ' (with Media Embed support, view mode enabled and two view modes configured )';
    $basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured['format'] = 'basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured';
    FilterFormat::create($basic_html_format_with_media_embed_view_mode_enabled_two_view_modes_configured)->save();
    $basic_html_editor_with_media_embed_view_mode_enabled_two_view_modes_configured = Editor::create(
      ['format' => 'basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured']
      +
      Yaml::parseFile('core/modules/ckeditor5/tests/fixtures/ckeditor4_config/editor.editor.basic_html.yml')
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
      'format' => 'cke4_stylescombo_span',
      'name' => 'A CKEditor 4 configured to have span styles',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <span class="llama">',
          ] + $filter_plugin_manager->getDefinition('filter_html')['settings'],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'cke4_stylescombo_span',
      'editor' => 'ckeditor',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Whatever',
                'items' => [
                  'Styles',
                ],
              ],
            ],
          ],
        ],
        'plugins' => [
          'stylescombo' => [
            'styles' => "span.llama|Llama span",
          ],
        ],
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
   * @param string[] $expected_db_logs
   *   The expected database logs associated with the computed settings.
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
  public function test(string $format_id, array $filters_to_drop, array $expected_ckeditor5_settings, string $expected_superset, array $expected_fundamental_compatibility_violations, array $expected_db_logs, array $expected_messages, ?array $expected_post_filter_drop_fundamental_compatibility_violations = NULL, ?array $expected_post_update_text_editor_violations = NULL): void {
    $text_format = FilterFormat::load($format_id);
    $text_editor = Editor::load($format_id);

    // Check the pre-CKE5 switch validation errors in case of a minimal (empty)
    // CKEditor 5 text editor config entity, to allow us to detect fundamental
    // compatibility problems, such as incompatible filters.
    $minimal_valid_cke5_text_editor = Editor::create([
      'format' => $format_id,
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
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
    // TRICKY: because we're validating using `editor.editor.*` as the config
    // name, TextEditorObjectDependentValidatorTrait will load the stored filter
    // format. That has not yet been updated at this point, so in order for
    // validation to pass, it must first be saved.
    // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\TextEditorObjectDependentValidatorTrait::createTextEditorObjectFromContext()
    // @todo Remove this work-around in https://www.drupal.org/project/drupal/issues/3231354
    $updated_text_editor->getFilterFormat()->save();
    $this->assertConfigSchema(
      $this->typedConfig,
      $updated_text_editor->getConfigDependencyName(),
      $updated_text_editor->toArray()
    );

    // Save this to ensure the config export order is applied.
    // @see \Drupal\Core\Config\StorableConfigBase::castValue()
    $updated_text_editor->save();

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

    $db_logged = $this
      ->database
      ->select('watchdog', 'w')
      ->fields('w', ['message', 'variables', 'severity'])
      ->condition('type', 'ckeditor5')
      ->orderBy('wid')
      ->execute()
      ->fetchAll();

    $type_to_status = [
      6 => 'status',
      4 => 'warning',
    ];
    $db_logs = [];
    foreach ($db_logged as $log) {
      $variables = unserialize($log->variables);
      $message = new FormattableMarkup($log->message, $variables);
      $db_logs[$type_to_status[$log->severity]][] = (string) $message;
    }

    // Transforms TranslatableMarkup objects to string.
    foreach ($messages as $type => $messages_per_type) {
      foreach ($messages_per_type as $key => $message) {
        $messages[$type][$key] = (string) $message;
      }
    }

    $this->assertSame($expected_db_logs, $db_logs);
    $this->assertSame($expected_messages, $messages);
  }

  /**
   * Data provider.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public static function provider() {
    $basic_html_test_case = [
      'format_id' => 'basic_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Default toolbar items.
            'heading',
            'bold',
            'italic',
            '|',
            // Items added based on "allowed tags" config.
            'link',
            'blockQuote',
            'code',
            'bulletedList',
            'numberedList',
            'drupalInsertImage',
            // Because additional tags need to be allowed to achieve a superset.
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
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
            ],
            'multiBlock' => TRUE,
          ],
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
        ],
      ],
      'expected_superset' => '<ol reversed>',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [],
    ];

    yield "basic_html can be switched to CKEditor 5 without problems (3 upgrade messages)" => NestedArray::mergeDeep(
      $basic_html_test_case,
      [
        'expected_db_logs' => [
          'status' => [
            'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
          ],
        ],
        'expected_messages' => [
          'status' => [
            'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
          ],
          'warning' => [
            'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
          ],
        ],
      ]
    );

    yield "basic_html with filter_caption removed => disallows <img data-caption> => supported through sourceEditing (3 upgrade messages)" => [
      'format_id' => 'basic_html',
      'filters_to_drop' => [
        'filter_caption' => FALSE,
      ],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_imageResize'],
          'ckeditor5_list' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_list'],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 0, 9),
              ['<img data-caption>'],
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 9),
            ),
          ],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          ...$basic_html_test_case['expected_db_logs']['status'],
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-caption&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-caption&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html with filter_align removed => disallows <img data-align> => supported through sourceEditing (3 upgrade messages) " => [
      'format_id' => 'basic_html',
      'filters_to_drop' => [
        'filter_align' => FALSE,
      ],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_imageResize'],
          'ckeditor5_list' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_list'],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 0, 9),
              ['<img data-align>'],
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 9),
            ),
          ],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          ...$basic_html_test_case['expected_db_logs']['status'],
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-align&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-align&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html_without_image_uploads can be switched to CKEditor 5 without problems, <img data-entity-type data-entity-uuid> support is retained via sourceEditing" => [
      'format_id' => 'basic_html_without_image_uploads',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_imageResize'],
          'ckeditor5_list' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_list'],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 0, 9),
              ['<img data-entity-type data-entity-uuid>'],
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 9),
            ),
          ],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (without image uploads)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (without image uploads)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (without image uploads)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-entity-type data-entity-uuid&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-entity-type data-entity-uuid&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html_without_h4_h6 can be switched to CKEditor 5 without problems" => [
      'format_id' => 'basic_html_without_h4_h6',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
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
          'ckeditor5_imageResize' => ['allow_resize' => TRUE],
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
            ],
            'multiBlock' => TRUE,
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_values(array_diff(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<h4 id>', '<h6 id>'],
            )),
          ],
        ],
      ],
      'expected_superset' => '<h4> <h6> ' . $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (without H4 and H6)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (without H4 and H6)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (without H4 and H6)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h5 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h5 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:  The tags <em class="placeholder">&lt;h4&gt;, &lt;h6&gt;</em>; This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html_with_h1 can be switched to CKEditor 5 without problems" => [
      'format_id' => 'basic_html_with_h1',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
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
          'ckeditor5_imageResize' => ['allow_resize' => TRUE],
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
            ],
            'multiBlock' => TRUE,
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 0, 5),
              ['<h1>'],
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 5),
            ),
          ],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (with &lt;h1&gt;)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (with &lt;h1&gt;)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;h1&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (with &lt;h1&gt;)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;h1&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html_without_headings can be switched to CKEditor 5 without problems" => [
      'format_id' => 'basic_html_without_headings',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
        ],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => ['allow_resize' => TRUE],
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
            ],
            'multiBlock' => TRUE,
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_values(array_diff(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<h2 id>', '<h3 id>', '<h4 id>', '<h5 id>', '<h6 id>'],
            )),
          ],
        ],
      ],
      'expected_superset' => '<h2> <h3> <h4> <h5> <h6> ' . $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (without H*)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (without H*)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (without H*)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:  The tags <em class="placeholder">&lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;</em>; This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html_with_pre can be switched to CKEditor 5 without problems" => [
      'format_id' => 'basic_html_with_pre',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 0, 10),
            ['codeBlock'],
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], -2),
          ),
        ],
        'plugins' => [
          'ckeditor5_codeBlock' => [
            'languages' => [
              ['label' => 'Plain text', 'language' => 'plaintext'],
              ['label' => 'C', 'language' => 'c'],
              ['label' => 'C#', 'language' => 'cs'],
              ['label' => 'C++', 'language' => 'cpp'],
              ['label' => 'CSS', 'language' => 'css'],
              ['label' => 'Diff', 'language' => 'diff'],
              ['label' => 'HTML', 'language' => 'html'],
              ['label' => 'Java', 'language' => 'java'],
              ['label' => 'JavaScript', 'language' => 'javascript'],
              ['label' => 'PHP', 'language' => 'php'],
              ['label' => 'Python', 'language' => 'python'],
              ['label' => 'Ruby', 'language' => 'ruby'],
              ['label' => 'TypeScript', 'language' => 'typescript'],
              ['label' => 'XML', 'language' => 'xml'],
            ],
          ],
        ] + $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
      ],
      'expected_superset' => '<ol reversed> <code class="language-*">',
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (with &lt;pre&gt;)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;) Code Block (for tags: &lt;pre&gt;)</em>. The text format must be saved to make these changes active.',
          str_replace('Basic HTML', 'Basic HTML (with &lt;pre&gt;)', $basic_html_test_case['expected_db_logs']['status'][1]),
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (with &lt;pre&gt;)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Code Block, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   These attributes: <em class="placeholder"> reversed (for &lt;ol&gt;), class (for &lt;code&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html_with_alignable_p can be switched to CKEditor 5 without problems, align buttons added automatically, but a superset of alignments is enabled" => [
      'format_id' => 'basic_html_with_alignable_p',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => array_merge(
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], 0, 10),
            ['alignment'],
            array_slice($basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'], -2),
          ),
        ],
        'plugins' => array_merge(
          [
            'ckeditor5_alignment' => [
              'enabled_alignments' => ['center', 'justify', 'left', 'right'],
            ],
          ],
          $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
        ),
      ],
      'expected_superset' => implode(' ', [
        // Note that aligning left and right is being added, on top of what the
        // original format allowed: center and justify.
        // Note that aligning left/center/right/justify is possible on *all*
        // allowed CKEditor 5 `$block` text container tags.
        // @todo When https://www.drupal.org/project/drupal/issues/3259367
        //   lands, none of the tags below should appear.
        '<p class="text-align-left text-align-right">',
        '<h2 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h3 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h4 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h5 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<h6 class="text-align-left text-align-center text-align-right text-align-justify">',
        '<ol reversed>',
      ]),
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (with alignable paragraph support)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (with alignable paragraph support)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'The CKEditor 5 migration process enabled the following plugins to support specific attributes that are allowed by the <em class="placeholder">Basic HTML (with alignable paragraph support)</em> text format: <em class="placeholder">Alignment ( for tag: &lt;p&gt; to support: class with value(s):  text-align-center, text-align-justify)</em>.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (with alignable paragraph support)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption, Alignment</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   These attributes: <em class="placeholder"> class (for &lt;p&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;), reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html with media_embed added (3 upgrade messages), but the drupalMedia toolbar item is not added automatically" => [
      'format_id' => 'basic_html_with_media_embed',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
        ],
        'plugins' => array_merge($basic_html_test_case['expected_ckeditor5_settings']['plugins'], ['media_media' => ['allow_view_mode_override' => FALSE]]),
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (with Media Embed support)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (with Media Embed support)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (with Media Embed support)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "basic_html with media_embed added with data-view-mode allowed and no view modes configured, but the drupalMedia toolbar item is not added automatically (3 upgrade messages, 1 violation)" => [
      'format_id' => 'basic_html_with_media_embed_view_mode_enabled_no_view_modes_configured',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
        ],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_imageResize'],
          'ckeditor5_list' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_list'],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<drupal-media data-view-mode>'],
            ),
          ],
          'media_media' => ['allow_view_mode_override' => FALSE],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">(with Media Embed support, view mode enabled but no view modes configured)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">(with Media Embed support, view mode enabled but no view modes configured)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">(with Media Embed support, view mode enabled but no view modes configured)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;drupal-media data-view-mode&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;drupal-media data-view-mode&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ]),
      'expected_post_filter_drop_fundamental_compatibility_violations' => [],
      'expected_post_update_text_editor_violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.14' => 'The following attribute(s) can optionally be supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: <em class="placeholder">Media (&lt;drupal-media data-view-mode&gt;)</em>.',
      ],
    ];

    yield "basic_html with media_embed added with data-view-mode allowed and 2 view modes configured, but the drupalMedia toolbar item is not added automatically (3 upgrade messages, 1 violation)" => [
      'format_id' => 'basic_html_with_media_embed_view_mode_enabled_two_view_modes_configured',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar']['items'],
        ],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_imageResize'],
          'ckeditor5_list' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_list'],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'],
              ['<drupal-media data-view-mode>'],
            ),
          ],
          'media_media' => ['allow_view_mode_override' => FALSE],
        ],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">(with Media Embed support, view mode enabled and two view modes configured )</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">(with Media Embed support, view mode enabled and two view modes configured )</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">(with Media Embed support, view mode enabled and two view modes configured )</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;drupal-media data-view-mode&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => array_merge_recursive($basic_html_test_case['expected_messages'], [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload, Image align, Image caption</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;drupal-media data-view-mode&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ]),
      'expected_post_filter_drop_fundamental_compatibility_violations' => [],
      'expected_post_update_text_editor_violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.14' => 'The following attribute(s) can optionally be supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: <em class="placeholder">Media (&lt;drupal-media data-view-mode&gt;)</em>.',
      ],
    ];

    yield "basic_html_with_any_data_attr can be switched to CKEditor 5 without problems (3 upgrade messages)" => [
      'format_id' => 'basic_html_with_any_data_attr',
      'filters_to_drop' => $basic_html_test_case['filters_to_drop'],
      'expected_ckeditor5_settings' => [
        'toolbar' => $basic_html_test_case['expected_ckeditor5_settings']['toolbar'],
        'plugins' => [
          'ckeditor5_heading' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_heading'],
          'ckeditor5_imageResize' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_imageResize'],
          'ckeditor5_list' => $basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_list'],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => array_merge(
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 0, 9),
              ['<img data-*>'],
              array_slice($basic_html_test_case['expected_ckeditor5_settings']['plugins']['ckeditor5_sourceEditing']['allowed_tags'], 9),
            ),
          ],
        ] + $basic_html_test_case['expected_ckeditor5_settings']['plugins'],
      ],
      'expected_superset' => $basic_html_test_case['expected_superset'],
      'expected_fundamental_compatibility_violations' => $basic_html_test_case['expected_fundamental_compatibility_violations'],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Basic HTML (with any data-* attribute on images)</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;) Image (for tags: &lt;img&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Basic HTML (with any data-* attribute on images)</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Basic HTML (with any data-* attribute on images)</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-*&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List, Image, Image Upload</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;span&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;img data-*&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:   This attribute: <em class="placeholder"> reversed (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "restricted_html can be switched to CKEditor 5 after dropping the two markup-creating filters (3 upgrade messages)" => [
      'format_id' => 'restricted_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Default toolbar items.
            'heading',
            'bold',
            'italic',
            '|',
            // Items added based on "allowed tags" config.
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
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
            ],
            'multiBlock' => TRUE,
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
        ],
      ],
      'expected_superset' => '<br> <p> <ol reversed>',
      'expected_fundamental_compatibility_violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Restricted HTML</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Restricted HTML</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Restricted HTML</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
        'warning' => [
          'As part of migrating the <em class="placeholder">Restricted HTML</em> text format to CKEditor 5, the following tag(s) were added to <em>Limit allowed HTML tags and correct faulty HTML</em>, because they are needed to provide fundamental CKEditor 5 functionality : &lt;br&gt; &lt;p&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following: The &lt;br&gt;, &lt;p&gt; tags were added because they are <a target="_blank" href="/admin/help/ckeditor5#required-tags">required by CKEditor 5</a>. The tags <em class="placeholder">&lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;, &lt;*&gt;, &lt;cite&gt;, &lt;dl&gt;, &lt;dt&gt;, &lt;dd&gt;, &lt;a&gt;, &lt;blockquote&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;code&gt;, &lt;li&gt;</em>; These attributes: <em class="placeholder"> id (for &lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;), dir (for &lt;*&gt;), lang (for &lt;*&gt;), hreflang (for &lt;a&gt;), href (for &lt;a&gt;), cite (for &lt;blockquote&gt;), type (for &lt;ul&gt;, &lt;ol&gt;), reversed (for &lt;ol&gt;), start (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
      'expected_post_filter_drop_fundamental_compatibility_violations' => [],
    ];

    yield "full_html can be switched to CKEditor 5 (no upgrade messages), uses only default toolbar items because impossible to know toolbar items are desired" => [
      'format_id' => 'full_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Default toolbar items.
            'heading',
            'bold',
            'italic',
            // Because there are no HTML restrictions.
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
      'expected_db_logs' => [],
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
            // Because additional tags need to be allowed to achieve a superset.
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
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
            ],
            'multiBlock' => TRUE,
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
        ],
      ],
      'expected_superset' => '<br> <p> <ol reversed>',
      'expected_fundamental_compatibility_violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Only the &quot;filter_html&quot; filter and its default settings</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;) Block quote (for tags: &lt;blockquote&gt;) Code (for tags: &lt;code&gt;) List (for tags: &lt;ul&gt;&lt;ol&gt;&lt;li&gt;)</em>. The text format must be saved to make these changes active.',
          'The following tags were permitted by the <em class="placeholder">Only the &quot;filter_html&quot; filter and its default settings</em> text format\'s filter configuration, but no plugin was available that supports them. To ensure the tags remain supported by this text format, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt;. The text format must be saved to make these changes active.',
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">Only the &quot;filter_html&quot; filter and its default settings</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type=&quot;1 A I&quot;&gt; &lt;h2 id=&quot;jump-*&quot;&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. The text format must be saved to make these changes active.',
        ],
        'warning' => [
          'As part of migrating the <em class="placeholder">Only the &quot;filter_html&quot; filter and its default settings</em> text format to CKEditor 5, the following tag(s) were added to <em>Limit allowed HTML tags and correct faulty HTML</em>, because they are needed to provide fundamental CKEditor 5 functionality : &lt;br&gt; &lt;p&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link, Block quote, Code, List</em>). Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;cite&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;a hreflang&gt; &lt;blockquote cite&gt; &lt;ul type&gt; &lt;ol type=&quot;1 A I&quot;&gt; &lt;h2 id=&quot;jump-*&quot;&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following: The &lt;br&gt;, &lt;p&gt; tags were added because they are <a target="_blank" href="/admin/help/ckeditor5#required-tags">required by CKEditor 5</a>. The tags <em class="placeholder">&lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;, &lt;*&gt;, &lt;cite&gt;, &lt;dl&gt;, &lt;dt&gt;, &lt;dd&gt;, &lt;a&gt;, &lt;blockquote&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;code&gt;, &lt;li&gt;</em>; These attributes: <em class="placeholder"> id (for &lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;), dir (for &lt;*&gt;), lang (for &lt;*&gt;), hreflang (for &lt;a&gt;), href (for &lt;a&gt;), cite (for &lt;blockquote&gt;), type (for &lt;ul&gt;, &lt;ol&gt;), reversed (for &lt;ol&gt;), start (for &lt;ol&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

    yield "cke4_stylescombo_span can be switched to CKEditor 5 without problems, only <span> in Source Editing" => [
      'format_id' => 'cke4_stylescombo_span',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Default toolbar items.
            'heading',
            'bold',
            'italic',
            // Because additional tags need to be allowed to achieve a superset.
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
            'allowed_tags' => [
              '<span class="llama">',
            ],
          ],
        ],
      ],
      'expected_superset' => '<h2> <h3> <h4> <h5> <h6> <strong> <em>',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [
        'status' => [
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">A CKEditor 4 configured to have span styles</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;span class=&quot;llama&quot;&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following:  Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;span class=&quot;llama&quot;&gt;. Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:  The tags <em class="placeholder">&lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;, &lt;strong&gt;, &lt;em&gt;</em>;  Additional details are available in your logs.',
        ],
      ],
    ];

    yield "minimal_ckeditor_wrong_allowed_html does not have sufficient allowed HTML => necessary allowed HTML added (1 upgrade message)" => [
      'format_id' => 'minimal_ckeditor_wrong_allowed_html',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            // Default toolbar items.
            'heading',
            'bold',
            'italic',
            '|',
            'link',
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
        ],
      ],
      'expected_superset' => '<h2> <h3> <h4> <h5> <h6> <strong> <em> <a href>',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [
        'status' => [
          'The CKEditor 5 migration enabled the following plugins to support tags that are allowed by the <em class="placeholder">Most basic HTML, but with allowed_html misconfigured</em> text format: <em class="placeholder">Link (for tags: &lt;a&gt;)</em>. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following: Enabled these plugins: (<em class="placeholder">Link</em>). . Additional details are available in your logs.',
        ],
        'warning' => [
          'Updating to CKEditor 5 added support for some previously unsupported tags/attributes. A plugin introduced support for the following:  The tags <em class="placeholder">&lt;h2&gt;, &lt;h3&gt;, &lt;h4&gt;, &lt;h5&gt;, &lt;h6&gt;, &lt;strong&gt;, &lt;em&gt;</em>; This attribute: <em class="placeholder"> href (for &lt;a&gt;)</em>; Additional details are available in your logs.',
        ],
      ],
    ];

  }

}
