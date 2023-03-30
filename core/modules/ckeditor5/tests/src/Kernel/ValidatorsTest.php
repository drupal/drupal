<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Symfony\Component\Yaml\Yaml;

// cspell:ignore onhover baguette

/**
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\ToolbarItemDependencyConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\EnabledConfigurablePluginsConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Editor\CKEditor5::validatePair
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator
 * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\CKEditor5MediaAndFilterSettingsInSyncConstraintValidator
 * @group ckeditor5
 */
class ValidatorsTest extends KernelTestBase {

  use SchemaCheckTestTrait;
  use CKEditor5ValidationTestTrait;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'ckeditor5_plugin_conditions_test',
    'editor',
    'filter',
    'filter_test',
    'media',
    'media_library',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedConfig = $this->container->get('config.typed');
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\CKEditor5ElementConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\StyleSensibleElementConstraintValidator
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\UniqueLabelInListConstraintValidator
   * @dataProvider provider
   *
   * @param array $ckeditor5_settings
   *   The CKEditor 5 settings to test.
   * @param array $expected_violations
   *   All expected violations for the given CKEditor 5 settings, with property
   *   path as keys and message as values.
   */
  public function test(array $ckeditor5_settings, array $expected_violations) {
    // The data provider is unable to access services, so the test scenario of
    // testing with CKEditor 5's default settings is partially provided here.
    if ($ckeditor5_settings === ['__DEFAULT__']) {
      $ckeditor5_settings = \Drupal::service('plugin.manager.editor')->createInstance('ckeditor5')->getDefaultSettings();
    }

    FilterFormat::create([
      'format' => 'dummy',
      'name' => 'Dummy',
    ])->save();
    $editor = Editor::create([
      'format' => 'dummy',
      'editor' => 'ckeditor5',
      'settings' => $ckeditor5_settings,
      'image_upload' => [],
    ]);

    $typed_config = $this->typedConfig->createFromNameAndData(
      $editor->getConfigDependencyName(),
      $editor->toArray(),
    );
    $violations = $typed_config->validate();

    $actual_violations = [];
    foreach ($violations as $violation) {
      $actual_violations[$violation->getPropertyPath()] = (string) $violation->getMessage();
    }
    $this->assertSame($expected_violations, $actual_violations);

    if (empty($expected_violations)) {
      $this->assertConfigSchema(
        $this->typedConfig,
        $editor->getConfigDependencyName(),
        $typed_config->getValue()
      );
    }
  }

  /**
   * Provides a list of Text Editor config entities using CKEditor 5 to test.
   */
  public function provider(): array {
    $data = [];
    $data['CKEditor5::getDefaultSettings()'] = [
      // @see ::test()
      'settings' => ['__DEFAULT__'],
      'violations' => [],
    ];
    $data['non-existent toolbar button'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'underline',
            'bold',
            'italic',
            '-',
            'bulletedList',
            'foobar',
          ],
        ],
        'plugins' => [
          'ckeditor5_list' => [
            'reversed' => FALSE,
            'startIndex' => FALSE,
          ],
        ],
      ],
      'violations' => [
        'settings.toolbar.items.5' => 'The provided toolbar item <em class="placeholder">foobar</em> is not valid.',
      ],
    ];

    $data['missing heading plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_heading' => 'Configuration for the enabled plugin "<em class="placeholder">Headings</em>" (<em class="placeholder">ckeditor5_heading</em>) is missing.',
      ],
    ];
    $data['missing language plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language' => 'Configuration for the enabled plugin "<em class="placeholder">Language</em>" (<em class="placeholder">ckeditor5_language</em>) is missing.',
      ],
    ];
    $data['empty language plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language' => 'Configuration for the enabled plugin "<em class="placeholder">Language</em>" (<em class="placeholder">ckeditor5_language</em>) is missing.',
      ],
    ];
    $data['valid language plugin configuration: un'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'un',
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['valid language plugin configuration: all'] = [
      'settings' => [
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
      'violations' => [],
    ];
    $data['invalid language plugin configuration: textPartLanguage button not enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'all',
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language.language_list' => 'Depends on <em class="placeholder">textPartLanguage</em>, which is not enabled.',
      ],
    ];
    $data['invalid language plugin configuration: invalid language_list setting'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'textPartLanguage',
          ],
        ],
        'plugins' => [
          'ckeditor5_language' => [
            'language_list' => 'foo',
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_language.language_list' => 'The value you selected is not a valid choice.',
      ],
    ];

    $data['drupalMedia toolbar item condition not met: media filter enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Drupal media</em> toolbar item requires the <em class="placeholder">Embed media</em> filter to be enabled.',
      ],
    ];
    $data['fooBarConditions toolbar item condition not met: Heading and Table plugins enabled, neither are'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'fooBarConditions',
          ],
        ],
        'plugins' => [],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Foo Bar (Test Plugins Condition)</em> toolbar item requires the <em class="placeholder">Headings, Table</em> plugins to be enabled.',
      ],
    ];
    $data['fooBarConditions toolbar item condition not met: Heading and Table plugins enabled, only one is'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'fooBarConditions',
            'heading',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Foo Bar (Test Plugins Condition)</em> toolbar item requires the <em class="placeholder">Table</em> plugin to be enabled.',
      ],
    ];
    $data['fooBarConditions toolbar item condition met: Heading and Table plugins enabled, both are'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'fooBarConditions',
            'heading',
            'insertTable',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
            ],
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['INVALID: Style plugin with no styles'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [],
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles' => 'Enable at least one style, otherwise disable the Style plugin.',
      ],
    ];
    $data['INVALID: Style plugin configured to add class to GHS-supported non-HTML5 tag'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<foo>',
            ],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Barry foo',
                'element' => '<foo class="bar">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles.0.element' => 'A style can only be specified for an HTML 5 tag. <code>&lt;foo&gt;</code> is not an HTML5 tag.',
      ],
    ];
    $data['INVALID: Style plugin configured to add class to plugin-supported non-HTML5 tag'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Sensational media',
                'element' => '<drupal-media class="sensational">',
              ],
            ],
          ],
          'media_media' => [
            'allow_view_mode_override' => FALSE,
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles.0.element' => 'A style can only be specified for an HTML 5 tag. <code>&lt;drupal-media&gt;</code> is not an HTML5 tag.',
      ],
    ];
    $data['INVALID: Style plugin configured to add class that is supported by a disabled plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Justified paragraph',
                'element' => '<p class="text-align-justify">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles.0.element' => 'A style must only specify classes not supported by other plugins. The <code>text-align-justify</code> classes on <code>&lt;p&gt;</code> are supported by the <em class="placeholder">Alignment</em> plugin. Remove this style and enable that plugin instead.',
      ],
    ];
    $data['INVALID: Style plugin configured to add class that is supported by an enabled plugin if its configuration were different'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
            'alignment',
          ],
        ],
        'plugins' => [
          'ckeditor5_alignment' => [
            'enabled_alignments' => ['center'],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Justified paragraph',
                'element' => '<p class="text-align-justify">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['INVALID: Style plugin configured to add class that is supported by an enabled plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
            'alignment',
          ],
        ],
        'plugins' => [
          'ckeditor5_alignment' => [
            'enabled_alignments' => ['justify'],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Justified paragraph',
                'element' => '<p class="text-align-justify">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles.0.element' => 'A style must only specify classes not supported by other plugins. The <code>text-align-justify</code> classes on <code>&lt;p&gt;</code> are already supported by the enabled <em class="placeholder">Alignment</em> plugin.',
      ],
    ];
    $data['INVALID: Style plugin has multiple styles with same label'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'blockQuote',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              0 => [
                'label' => 'Highlighted',
                'element' => '<p class="highlighted">',
              ],
              1 => [
                'label' => 'Highlighted',
                'element' => '<blockquote class="highlighted">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles' => 'The label <em class="placeholder">Highlighted</em> is not unique.',
      ],
    ];
    $data['INVALID: Style plugin has styles with invalid elements'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'blockQuote',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              0 => [
                'label' => 'missing class attribute',
                'element' => '<p>',
              ],
              1 => [
                'label' => 'class attribute present but no allowed values listed',
                'element' => '<blockquote class="">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles.0.element' => 'The following tag is missing the required attribute <code>class</code>: <code>&lt;p&gt;</code>.',
        'settings.plugins.ckeditor5_style.styles.1.element' => 'The following tag does not have the minimum of 1 allowed values for the required attribute <code>class</code>: <code>&lt;blockquote class=&quot;&quot;&gt;</code>.',
      ],
    ];
    $data['VALID: Style plugin has multiple styles with different labels'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'blockQuote',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Callout',
                'element' => '<p class="callout">',
              ],
              [
                'label' => 'Interesting & highlighted quote',
                'element' => '<blockquote class="interesting highlighted">',
              ],
              [
                'label' => 'Famous',
                'element' => '<blockquote class="famous">',
              ],
            ],
          ],
        ],
      ],
      'violations' => [],
    ];

    return $data;
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\SourceEditingPreventSelfXssConstraintValidator
   * @dataProvider providerPair
   *
   * @param array $ckeditor5_settings
   *   The paired text editor's CKEditor 5 settings to test.
   * @param array $editor_image_upload_settings
   *   The paired text editor's image upload settings to test.
   * @param array $filters
   *   The paired text format's filters and filter settings.
   * @param array $expected_violations
   *   All expected violations for the pair.
   */
  public function testPair(array $ckeditor5_settings, array $editor_image_upload_settings, array $filters, array $expected_violations) {
    $text_editor = Editor::create([
      'format' => 'dummy',
      'editor' => 'ckeditor5',
      'settings' => $ckeditor5_settings,
      'image_upload' => $editor_image_upload_settings,
    ]);
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
    assert($text_editor instanceof EditorInterface);
    $this->assertConfigSchema(
      $this->typedConfig,
      $text_editor->getConfigDependencyName(),
      $text_editor->toArray()
    );
    $text_format = FilterFormat::create([
      'filters' => $filters,
    ]);
    assert($text_format instanceof FilterFormatInterface);

    $this->assertSame($expected_violations, $this->validatePairToViolationsArray($text_editor, $text_format, TRUE));
  }

  /**
   * Provides a list of Text Editor + Text Format pairs to test.
   */
  public function providerPair(): array {
    // cspell:ignore donk
    $data = [];
    $data['INVALID: allow_view_mode_override condition not met: filter must be configured to allow 2 or more view modes'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [
          'media_media' => [
            'allow_view_mode_override' => TRUE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'media_embed' => [
          'id' => 'media_embed',
          'provider' => 'media',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'default_view_mode' => 'default',
            'allowed_view_modes' => [],
            'allowed_media_types' => [],
          ],
        ],
      ],
      'violations' => [
        '' => 'The CKEditor 5 "<em class="placeholder">Media</em>" plugin\'s "<em class="placeholder">Allow the user to override the default view mode</em>" setting should be in sync with the "<em class="placeholder">Embed media</em>" filter\'s "<em class="placeholder">View modes selectable in the &quot;Edit media&quot; dialog</em>" setting: when checked, two or more view modes must be allowed by the filter.',
      ],
    ];
    $data['VALID: allow_view_mode_override condition met: filter must be configured to allow 2 or more view modes'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [
          'media_media' => [
            'allow_view_mode_override' => TRUE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'media_embed' => [
          'id' => 'media_embed',
          'provider' => 'media',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'default_view_mode' => 'view_mode_1',
            'allowed_view_modes' => [
              'view_mode_1' => 'view_mode_1',
              'view_mode_2' => 'view_mode_2',
            ],
            'allowed_media_types' => [],
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: legacy format: filter_autop'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_autop' => [
          'id' => 'filter_autop',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: legacy HTML format: filter_autop + filter_url'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_autop' => [
          'id' => 'filter_autop',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [],
        ],
        'filter_url' => [
          'id' => 'filter_url',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => -10,
          'settings' => [
            'filter_url_length' => 72,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: legacy HTML format: filter_autop + filter_url (different order)'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_autop' => [
          'id' => 'filter_autop',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [],
        ],
        'filter_url' => [
          'id' => 'filter_url',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 10,
          'settings' => [
            'filter_url_length' => 72,
          ],
        ],
      ],
      'violations' => [],
    ];
    $restricted_html_format_filters = Yaml::parseFile(__DIR__ . '/../../../../../profiles/standard/config/install/filter.format.restricted_html.yml')['filters'];
    $data['INVALID: the default restricted_html text format'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => $restricted_html_format_filters,
      'violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
    ];
    $data['INVALID: the modified restricted_html text format (with filter_autop and filter_url removed)'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => array_diff_key(
        $restricted_html_format_filters,
        ['filter_autop' => TRUE, 'filter_url' => TRUE]
      ),
      'violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
    ];
    $data['VALID: HTML format: empty toolbar + minimal allowed HTML'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<p> <br>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: HTML format: very minimal toolbar + minimal allowed HTML'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<p> <br> <strong>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['INVALID: HTML format: empty toolbar + default allowed HTML tags + <p> + <br>'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id>" . "<p> <br>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'filters.filter_html' => sprintf(
          'The current CKEditor 5 build requires the following elements and attributes: <br><code>%s</code><br>The following elements are not supported: <br><code>%s</code>',
          Html::escape('<br> <p> <* dir="ltr rtl" lang>'),
          Html::escape('<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type="1 A I"> <li> <dl> <dt> <dd> <h2 id="jump-*"> <h3 id> <h4 id> <h5 id> <h6 id>'),
        ),
      ],
    ];
    $data['INVALID: HTML format: empty toolbar + default allowed HTML tags'] = [
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        '' => 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "<em class="placeholder">Limit allowed HTML tags and correct faulty HTML</em>" (<em class="placeholder">filter_html</em>) filter.',
      ],
    ];
    $data['INVALID Source Editable tag already provided by plugin and another available in a not enabled plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'bold',
            'italic',
            'link',
            'sourceEditing',
            'textPartLanguage',
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
          'ckeditor5_language' => [
            'language_list' => 'un',
          ],
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              // Tag-only; supported by enabled plugin.
              '<strong>',
              // Tag-only; supported by disabled plugin.
              '<table>',
              // Tag-only; supported by no plugin.
              '<exotic>',
              // Tag + attributes; all supported by enabled plugin.
              '<span lang>',
              // Tag + attributes; all supported by an ineligible disabled
              // plugin (has no toolbar item, has conditions).
              '<img src>',
              // Tag + attributes; attributes supported by disabled plugin.
              '<code class="language-*">',
              // Tag + attributes; tag already supported by enabled plugin,
              // attributes supported by disabled plugin
              '<h2 class="text-align-center">',
              // Tag + attributes; tag already supported by enabled plugin,
              // attribute not supported by no plugin.
              '<a hreflang>',
              // Tag-only; supported by no plugin (only attributes on tag
              // supported by a plugin).
              '<span>',
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
      ],
      'filters' => [],
      'violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.0' => 'The following tag(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: <em class="placeholder">Bold (&lt;strong&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.1' => 'The following tag(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these tags: <em class="placeholder">Table (&lt;table&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.3' => 'The following attribute(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: <em class="placeholder">Language (&lt;span lang&gt;)</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.5' => 'The following attribute(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these attributes: <em class="placeholder">Code Block (&lt;code class=&quot;language-*&quot;&gt;)</em>.',
        // @todo "Style" should be removed from the suggestions in https://www.drupal.org/project/drupal/issues/3271179
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.6' => 'The following attribute(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these attributes: <em class="placeholder">Style (&lt;h2 class=&quot;text-align-center&quot;&gt;), Alignment (&lt;h2 class=&quot;text-align-center&quot;&gt;)</em>.',
      ],
    ];
    $data['INVALID some invalid Source Editable tags provided by plugin and another available in a not enabled plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'bold',
            'italic',
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
              '<aside>',
              '<footer>',
              'roy',
              '<#donk>',
              '<junior>cruft',
              '',
              '   ',
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
      ],
      'filters' => [],
      'violations' => [
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.2' => 'The following tag is not valid HTML: <em class="placeholder">roy</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.3' => 'The following tag is not valid HTML: <em class="placeholder">&lt;#donk&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.4' => 'The following tag is not valid HTML: <em class="placeholder">&lt;junior&gt;cruft</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.5' => 'The following tag is not valid HTML: <em class="placeholder"></em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.6' => 'The following tag is not valid HTML: <em class="placeholder">   </em>.',
      ],
    ];

    $data['INVALID: drupalInsertImage without required dependent plugin configuration'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalInsertImage',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [
        'settings.plugins.ckeditor5_imageResize' => 'Configuration for the enabled plugin "<em class="placeholder">Image resize</em>" (<em class="placeholder">ckeditor5_imageResize</em>) is missing.',
      ],
    ];
    $data['VALID: drupalInsertImage toolbar item without image upload'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalInsertImage',
          ],
        ],
        'plugins' => [
          'ckeditor5_imageResize' => [
            'allow_resize' => FALSE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [],
    ];
    $data['VALID: drupalInsertImage image upload enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalInsertImage',
          ],
        ],
        'plugins' => [
          'ckeditor5_imageResize' => [
            'allow_resize' => FALSE,
          ],
        ],
      ],
      'image' => [
        'status' => TRUE,
      ],
      'filters' => [],
      'violations' => [],
    ];
    $data['INVALID: drupalMedia toolbar item condition NOT met: media filter enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Drupal media</em> toolbar item requires the <em class="placeholder">Embed media</em> filter to be enabled.',
      ],
    ];
    $data['VALID: drupalMedia toolbar item condition met: media filter enabled'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
          ],
        ],
        'plugins' => [],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'media_embed',
          'provider' => 'media',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'default_view_mode' => 'default',
            'allowed_view_modes' => [],
            'allowed_media_types' => [],
          ],
        ],
      ],
      'violations' => [
        'settings.toolbar.items.0' => 'The <em class="placeholder">Drupal media</em> toolbar item requires the <em class="placeholder">Embed media</em> filter to be enabled.',
      ],
    ];
    $data['VALID: HTML format: very minimal toolbar + wildcard in source editing HTML'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'bold',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => ['<$text-container data-llama>'],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p data-llama> <br> <strong>',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $self_xss_source_editing = [
      // Dangerous attribute with all values allowed.
      '<p onhover>',
      '<img on*>',
      '<blockquote style>',

      // No danger.
      '<marquee>',

      // Dangerous attribute with some values allowed.
      '<a onclick="javascript:*">',
      '<code style="foo: bar;">',

      // Also works on wildcard tags.
      '<$text-container style>',
    ];
    $data['INVALID: SourceEditing plugin configuration: self-XSS detected when using filter_html'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => $self_xss_source_editing,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p onhover style> <br> <img on*> <blockquote style> <marquee> <a onclick="javascript:*"> <code style="foo: bar;">',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'filters.filter_html' => 'The current CKEditor 5 build requires the following elements and attributes: <br><code>&lt;br&gt; &lt;p onhover style&gt; &lt;* dir=&quot;ltr rtl&quot; lang&gt; &lt;img on*&gt; &lt;blockquote style&gt; &lt;marquee&gt; &lt;a onclick=&quot;javascript:*&quot;&gt; &lt;code style=&quot;foo: bar;&quot;&gt;</code><br>The following elements are missing: <br><code>&lt;p onhover style&gt; &lt;img on*&gt; &lt;blockquote style&gt; &lt;code style=&quot;foo: bar;&quot;&gt;</code>',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.0' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;p onhover&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.1' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;img on*&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.2' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;blockquote style&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.4' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;a onclick=&quot;javascript:*&quot;&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.5' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;code style=&quot;foo: bar;&quot;&gt;</em>.',
        'settings.plugins.ckeditor5_sourceEditing.allowed_tags.6' => 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: <em class="placeholder">&lt;$text-container style&gt;</em>.',
      ],
    ];
    $data['VALID: SourceEditing plugin configuration: self-XSS not detected when not using filter_html'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => $self_xss_source_editing,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [],
      'violations' => [],
    ];
    $data['INVALID: Style plugin configured to add class to unsupported tag'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Highlighted',
                'element' => '<blockquote class="highlighted">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p> <br> <blockquote class="highlighted">',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style' => 'The <em class="placeholder">Style</em> plugin needs another plugin to create <code>&lt;blockquote&gt;</code>, for it to be able to create the following attributes: <code>&lt;blockquote class=&quot;highlighted&quot;&gt;</code>. Enable a plugin that supports creating this tag. If none exists, you can configure the Source Editing plugin to support it.',
      ],
    ];
    $data['INVALID: Style plugin configured to add class already added by an other plugin'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'alignment',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_alignment' => [
            'enabled_alignments' => ['justify'],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Text align',
                'element' => '<p class="text-align-justify">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p class="text-align-justify"> <br>',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [
        'settings.plugins.ckeditor5_style.styles.0.element' => 'A style must only specify classes not supported by other plugins. The <code>text-align-justify</code> classes on <code>&lt;p&gt;</code> are already supported by the enabled <em class="placeholder">Alignment</em> plugin.',
      ],
    ];
    $data['VALID: Style plugin configured to add new class to an already restricted tag'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'alignment',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_alignment' => [
            'enabled_alignments' => ['justify'],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Add baguette class',
                'element' => '<p class="baguette">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p class="text-align-justify baguette"> <br>',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: Style plugin configured to add class to an element provided by an explicit plugin that already allows all classes'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'kbdAllClasses',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Add baguette class',
                'element' => '<kbd class="baguette">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p> <br> <kbd class>',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: Style plugin configured to add class to GHS-supported HTML5 tag'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<kbd>',
            ],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Add baguette class',
                'element' => '<kbd class="baguette">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p> <br> <kbd class="baguette">',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    $data['VALID: Style plugin configured to add class to GHS-supported HTML5 tag that already allows all classes'] = [
      'settings' => [
        'toolbar' => [
          'items' => [
            'style',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<bdi class>',
            ],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Bidirectional name',
                'element' => '<bdi class="name">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
      'filters' => [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => '<p> <br> <bdi class>',
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
      ],
      'violations' => [],
    ];
    return $data;
  }

  /**
   * Tests that validation works with >1 enabled HTML restrictor filters.
   *
   * @covers \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator::checkHtmlRestrictionsMatch
   */
  public function testMultipleHtmlRestrictingFilters(): void {
    $this->container->get('module_installer')->install(['filter_test']);

    $text_format = FilterFormat::create([
      'format' => 'very_restricted',
      'name' => $this->randomMachineName(),
      'filters' => [
        // The first filter of type TYPE_HTML_RESTRICTOR.
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'allowed_html' => "<p> <br>",
            'filter_html_help' => TRUE,
            'filter_html_nofollow' => TRUE,
          ],
        ],
        // The second filter of type TYPE_HTML_RESTRICTOR. Configure this to
        // allow exactly what the first filter allows.
        'filter_test_restrict_tags_and_attributes' => [
          'id' => 'filter_test_restrict_tags_and_attributes',
          'provider' => 'filter_test',
          'status' => TRUE,
          'settings' => [
            'restrictions' => [
              'allowed' => [
                'p' => FALSE,
                'br' => FALSE,
                '*' => [
                  'dir' => ['ltr' => TRUE, 'rtl' => TRUE],
                  'lang' => TRUE,
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
    $text_editor = Editor::create([
      'format' => 'very_restricted',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [],
        ],
        'plugins' => [],
      ],
      'image_upload' => [],
    ]);

    $this->assertSame([], $this->validatePairToViolationsArray($text_editor, $text_format, TRUE));
  }

}
