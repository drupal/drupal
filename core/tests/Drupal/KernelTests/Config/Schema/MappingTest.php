<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Config\Schema;

// cspell:ignore childkey

use Drupal\block\Entity\Block;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Schema\Mapping
 * @group Config
 */
class MappingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    'config_schema_deprecated_test.settings',
  ];

  /**
   * @dataProvider providerMappingInterpretation
   */
  public function testMappingInterpretation(
    string $config_name,
    ?string $property_path,
    array $expected_valid_keys,
    array $expected_optional_keys,
    array $expected_dynamically_valid_keys,
  ): void {
    // Some config needs some dependencies installed.
    switch ($config_name) {
      case 'block.block.branding':
        $this->enableModules(['system', 'block']);
        /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
        $theme_installer = $this->container->get('theme_installer');
        $theme_installer->install(['stark']);
        Block::create([
          'id' => 'branding',
          'plugin' => 'system_branding_block',
          'theme' => 'stark',
          'status' => TRUE,
          'settings' => [
            'use_site_logo' => TRUE,
            'use_site_name' => TRUE,
            'use_site_slogan' => TRUE,
            'label_display' => FALSE,
            // This is inherited from `type: block_settings`.
            'context_mapping' => [],
          ],
        ])->save();
        break;

      case 'block.block.local_tasks':
        $this->enableModules(['system', 'block']);
        /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
        $theme_installer = $this->container->get('theme_installer');
        $theme_installer->install(['stark']);
        Block::create([
          'id' => 'local_tasks',
          'plugin' => 'local_tasks_block',
          'theme' => 'stark',
          'status' => TRUE,
          'settings' => [
            'primary' => TRUE,
            'secondary' => FALSE,
            // This is inherited from `type: block_settings`.
            'context_mapping' => [],
          ],
        ])->save();
        break;

      case 'block.block.positively_powered___alternate_reality_with_fallback_type___':
        $this->enableModules(['config_schema_add_fallback_type_test']);
        $id = 'positively_powered___alternate_reality_with_fallback_type___';
      case 'block.block.positively_powered':
        $this->enableModules(['system', 'block']);
        /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
        $theme_installer = $this->container->get('theme_installer');
        $theme_installer->install(['stark']);
        Block::create([
          'id' => $id ?? 'positively_powered',
          'plugin' => 'system_powered_by_block',
          'theme' => 'stark',
          'status' => TRUE,
          'settings' => [
            'label_display' => FALSE,
            // This is inherited from `type: block_settings`.
            'context_mapping' => [],
          ],
          // Avoid showing "Powered by Drupal" on 404 responses.
          'visibility' => [
            'I_CAN_CHOOSE_THIS' => [
              // This is what determines the
              'id' => 'response_status',
              'negate' => FALSE,
              'status_codes' => [
                404,
              ],
            ],
          ],
        ])->save();
        break;

      case 'config_schema_deprecated_test.settings':
        $this->enableModules(['config_schema_deprecated_test']);
        $config = $this->config('config_schema_deprecated_test.settings');
        // @see \Drupal\KernelTests\Core\Config\ConfigSchemaDeprecationTest
        $config
          ->set('complex_structure_deprecated.type', 'fruits')
          ->set('complex_structure_deprecated.products', ['apricot', 'apple'])
          ->save();
        break;

      case 'editor.editor.funky':
        $this->enableModules(['filter', 'editor', 'ckeditor5']);
        FilterFormat::create(['format' => 'funky', 'name' => 'Funky'])->save();
        Editor::create([
          'format' => 'funky',
          'editor' => 'ckeditor5',
          'image_upload' => [
            'status' => FALSE,
          ],
        ])->save();
        break;

      case 'field.field.node.config_mapping_test.comment_config_mapping_test':
        $this->enableModules(['user', 'field', 'node', 'comment', 'taxonomy', 'config_mapping_test']);
        $this->installEntitySchema('user');
        $this->installEntitySchema('node');
        $this->assertNull(FieldConfig::load('node.config_mapping_test.comment_config_mapping_test'));
        // \Drupal\node\Entity\NodeType::$preview_mode uses DRUPAL_OPTIONAL,
        // which is defined in system.module.
        require_once 'core/modules/system/system.module';
        $this->installConfig(['config_mapping_test']);
        $this->assertNotNull(FieldConfig::load('node.config_mapping_test.comment_config_mapping_test'));
        break;
    }

    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');
    $mapping = $typed_config_manager->get($config_name);
    if ($property_path) {
      $mapping = $mapping->get($property_path);
    }

    assert($mapping instanceof Mapping);
    $expected_required_keys = array_values(array_diff($expected_valid_keys, $expected_optional_keys));
    $this->assertSame($expected_valid_keys, $mapping->getValidKeys());
    $this->assertSame($expected_required_keys, $mapping->getRequiredKeys());
    $this->assertSame($expected_dynamically_valid_keys, $mapping->getDynamicallyValidKeys());
    $this->assertSame($expected_optional_keys, $mapping->getOptionalKeys());
  }

  /**
   * Provides test cases for all kinds of i) dynamic typing, ii) optional keys.
   *
   * @see https://www.drupal.org/files/ConfigSchemaCheatSheet2.0.pdf
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerMappingInterpretation(): \Generator {
    $available_block_settings_types = [
      'block.settings.field_block:*:*:*' => [
        'formatter',
      ],
      'block.settings.extra_field_block:*:*:*' => [
        'formatter',
      ],
      'block.settings.system_branding_block' => [
        'use_site_logo',
        'use_site_name',
        'use_site_slogan',
      ],
      'block.settings.system_menu_block:*' => [
        'level',
        'depth',
        'expand_all_items',
      ],
      'block.settings.local_tasks_block' => [
        'primary',
        'secondary',
      ],
    ];

    // A simple config often is just a single Mapping object.
    yield 'No dynamic type: core.extension' => [
      'core.extension',
      NULL,
      [
        // Keys inherited from `type: config_object`.
        // @see core/config/schema/core.data_types.schema.yml
        '_core',
        'langcode',
        // Keys defined locally, in `type: core.extension`.
        // @see core/config/schema/core.extension.schema.yml
        'module',
        'theme',
        'profile',
      ],
      [
        '_core',
        'langcode',
        'profile',
      ],
      [],
    ];

    // Special case: deprecated  is needed for deprecated config schema:
    // - deprecated keys are treated as optional
    // - if a deprecated property path is itself a mapping, then the keys inside
    //   are not optional
    yield 'No dynamic type: config_schema_deprecated_test.settings' => [
      'config_schema_deprecated_test.settings',
      NULL,
      [
        // Keys inherited from `type: config_object`.
        // @see core/config/schema/core.data_types.schema.yml
        '_core',
        'langcode',
        // Keys defined locally, in `type:
        // config_schema_deprecated_test.settings`.
        // @see core/modules/config/tests/config_schema_deprecated_test/config/schema/config_schema_deprecated_test.schema.yml
        'complex_structure_deprecated',
      ],
      ['_core', 'langcode', 'complex_structure_deprecated'],
      [],
    ];
    yield 'No dynamic type: config_schema_deprecated_test.settings:complex_structure_deprecated' => [
      'config_schema_deprecated_test.settings',
      'complex_structure_deprecated',
      [
        // Keys defined locally, in `type:
        // config_schema_deprecated_test.settings`.
        // @see core/modules/config/tests/config_schema_deprecated_test/config/schema/config_schema_deprecated_test.schema.yml
        'type',
        'products',
      ],
      [],
      [],
    ];

    // A config entity is always a Mapping at the top level, but most nesting is
    // also using Mappings (unless the keys are free to be chosen, then a
    // Sequence would be used).
    yield 'No dynamic type: block.block.branding' => [
      'block.block.branding',
      NULL,
      [
        // Keys inherited from `type: config_entity`.
        // @see core/config/schema/core.data_types.schema.yml
        'uuid',
        'langcode',
        'status',
        'dependencies',
        'third_party_settings',
        '_core',
        // Keys defined locally, in `type: block.block.*`.
        // @see core/modules/block/config/schema/block.schema.yml
        'id',
        'theme',
        'region',
        'weight',
        'provider',
        'plugin',
        'settings',
        'visibility',
      ],
      ['third_party_settings', '_core'],
      [],
    ];

    // An example of nested Mapping objects in config entities.
    yield 'No dynamic type: block.block.branding:dependencies' => [
      'block.block.branding',
      'dependencies',
      [
        // Keys inherited from `type: config_dependencies_base`.
        // @see core/config/schema/core.data_types.schema.yml
        'config',
        'content',
        'module',
        'theme',
        // Keys defined locally, in `type: config_dependencies`.
        // @see core/config/schema/core.data_types.schema.yml
        'enforced',
      ],
      // All these keys are optional!
      ['config', 'content', 'module', 'theme', 'enforced'],
      [],
    ];

    // Three examples of `[%parent]`-based dynamic typing in config schema, and
    // the consequences on what keys are considered valid: the first 2 depend
    // on the block plugin being used using a single `%parent`, the third
    // depends on the field plugin being used using a double `%parent`.
    // See `type: block.block.*` which uses
    // `type: block.settings.[%parent.plugin]`, and `type: field_config_base`
    // which uses `type: field.value.[%parent.%parent.field_type]`.
    yield 'Dynamic type with [%parent]: block.block.branding:settings' => [
      'block.block.branding',
      'settings',
      [
        // Keys inherited from `type: block.settings.*`, which in turn is
        // inherited from `type: block_settings`.
        // @see core/config/schema/core.data_types.schema.yml
        'id',
        'label',
        'label_display',
        'provider',
        'context_mapping',
        // Keys defined locally, in `type:
        // block.settings.system_branding_block`.
        // @see core/modules/block/config/schema/block.schema.yml
        ...$available_block_settings_types['block.settings.system_branding_block'],
      ],
      // This key is optional, see `type: block_settings`.
      // @see core.data_types.schema.yml
      ['context_mapping'],
      $available_block_settings_types,
    ];
    yield 'Dynamic type with [%parent]: block.block.local_tasks:settings' => [
      'block.block.local_tasks',
      'settings',
      [
        // Keys inherited from `type: block.settings.*`, which in turn is
        // inherited from `type: block_settings`.
        // @see core/config/schema/core.data_types.schema.yml
        'id',
        'label',
        'label_display',
        'provider',
        'context_mapping',
        // Keys defined locally, in `type: block.settings.local_tasks_block`.
        // @see core/modules/system/config/schema/system.schema.yml
        ...$available_block_settings_types['block.settings.local_tasks_block'],
      ],
      // This key is optional, see `type: block_settings`.
      // @see core.data_types.schema.yml
      ['context_mapping'],
      $available_block_settings_types,
    ];
    yield 'Dynamic type with [%parent.%parent]: field.field.node.config_mapping_test.comment_config_mapping_test:default_value.0' => [
      'field.field.node.config_mapping_test.comment_config_mapping_test',
      'default_value.0',
      [
        // Keys defined locally, in `type: field.value.comment`.
        // @see core/modules/comment/config/schema/comment.schema.yml
        'status',
        'cid',
        'last_comment_timestamp',
        'last_comment_name',
        'last_comment_uid',
        'comment_count',
      ],
      [],
      [
        'field.value.string' => ['value'],
        'field.value.string_long' => ['value'],
        'field.value.uri' => ['value'],
        'field.value.created' => ['value'],
        'field.value.changed' => ['value'],
        'field.value.entity_reference' => ['target_id', 'target_uuid'],
        'field.value.boolean' => ['value'],
        'field.value.email' => ['value'],
        'field.value.integer' => ['value'],
        'field.value.decimal' => ['value'],
        'field.value.float' => ['value'],
        'field.value.timestamp' => ['value'],
        'field.value.language' => ['value'],
        'field.value.comment' => [
          'status',
          'cid',
          'last_comment_timestamp',
          'last_comment_name',
          'last_comment_uid',
          'comment_count',
        ],
      ],
    ];

    // An example of `[childkey]`-based dynamic mapping typing in config schema,
    // for a mapping inside a sequence: the `id` key-value pair in the mapping
    // determines the type of the mapping. The key in the sequence whose value
    // is the mapping is irrelevant, it can be arbitrarily chosen.
    // See `type: block.block.*` which uses `type: condition.plugin.[id]`.
    yield 'Dynamic type with [childkey]: block.block.positively_powered:visibility.I_CAN_CHOOSE_THIS' => [
      'block.block.positively_powered',
      'visibility.I_CAN_CHOOSE_THIS',
      [
        // Keys inherited from `type: condition.plugin`.
        // @see core/config/schema/core.data_types.schema.yml
        'id',
        'negate',
        'uuid',
        'context_mapping',
        // Keys defined locally, in `type: condition.plugin.response_status`.
        // @see core/modules/system/config/schema/system.schema.yml
        'status_codes',
      ],
      [],
      // Note the presence of `id`, `negate`, `uuid` and `context_mapping` here.
      // That's because there is no `condition.plugin.*` type that specifies
      // defaults. Each individual condition plugin has the freedom to deviate
      // from this approach!
      [
        'condition.plugin.entity_bundle:*' => [
          'id',
          'negate',
          'uuid',
          'context_mapping',
          'bundles',
        ],
        'condition.plugin.request_path' => [
          'id',
          'negate',
          'uuid',
          'context_mapping',
          'pages',
        ],
        'condition.plugin.response_status' => [
          'id',
          'negate',
          'uuid',
          'context_mapping',
          'status_codes',
        ],
        'condition.plugin.current_theme' => [
          'id',
          'negate',
          'uuid',
          'context_mapping',
          'theme',
        ],
      ],
    ];
    // Same, but what if `type: condition.plugin.*` would have existed?
    // @see core/modules/config/tests/config_schema_add_fallback_type_test/config/schema/config_schema_add_fallback_type_test.schema.yml
    yield 'Dynamic type with [childkey]: block.block.positively_powered___alternate_reality_with_fallback_type___:visibility' => [
      'block.block.positively_powered___alternate_reality_with_fallback_type___',
      'visibility.I_CAN_CHOOSE_THIS',
      [
        // Keys inherited from `type: condition.plugin`.
        // @see core/config/schema/core.data_types.schema.yml
        'id',
        'negate',
        'uuid',
        'context_mapping',
        // Keys defined locally, in `type: condition.plugin.response_status`.
        // @see core/modules/system/config/schema/system.schema.yml
        'status_codes',
      ],
      [],
      // Note the ABSENCE of `id`, `negate`, `uuid` and `context_mapping`
      // compared to the previous test case, because now the
      // `condition.plugin.*` type does exist.
      [
        'condition.plugin.entity_bundle:*' => [
          'bundles',
        ],
        'condition.plugin.request_path' => [
          'pages',
        ],
        'condition.plugin.response_status' => [
          'status_codes',
        ],
        'condition.plugin.current_theme' => [
          'theme',
        ],
      ],
    ];

    // An example of `[%key]`-based dynamic mapping typing in config schema: the
    // key in the sequence determines the type of the mapping. Unlike the above
    // `[childkey]` example, the key has meaning here.
    // See `type: editor.settings.ckeditor5`, which uses
    // `type: ckeditor5.plugin.[%key]`.
    yield 'Dynamic type with [%key]: editor.editor.funky:settings.plugins.ckeditor5_heading' => [
      'editor.editor.funky',
      'settings.plugins.ckeditor5_heading',
      [
        // Keys defined locally, in `type: ckeditor5.plugin.ckeditor5_heading`.
        // @see core/modules/ckeditor5/config/schema/ckeditor5.schema.yml
        'enabled_headings',
      ],
      [],
      [
        'ckeditor5.plugin.ckeditor5_language' => ['language_list'],
        'ckeditor5.plugin.ckeditor5_heading' => ['enabled_headings'],
        'ckeditor5.plugin.ckeditor5_imageResize' => ['allow_resize'],
        'ckeditor5.plugin.ckeditor5_sourceEditing' => ['allowed_tags'],
        'ckeditor5.plugin.ckeditor5_alignment' => ['enabled_alignments'],
        'ckeditor5.plugin.ckeditor5_list' => ['properties', 'multiBlock'],
        'ckeditor5.plugin.media_media' => ['allow_view_mode_override'],
        'ckeditor5.plugin.ckeditor5_codeBlock' => ['languages'],
        'ckeditor5.plugin.ckeditor5_style' => ['styles'],
      ],
    ];
  }

  /**
   * @testWith [false, 42, "The mapping definition at `foobar` is invalid: its `invalid` key contains a integer. It must be an array."]
   *           [false, 10.2, "The mapping definition at `foobar` is invalid: its `invalid` key contains a double. It must be an array."]
   *           [false, "type", "The mapping definition at `foobar` is invalid: its `invalid` key contains a string. It must be an array."]
   *           [false, false, "The mapping definition at `foobar` is invalid: its `invalid` key contains a boolean. It must be an array."]
   *           [true, 42, "The mapping definition at `my_module.settings:foobar` is invalid: its `invalid` key contains a integer. It must be an array."]
   *           [true, 10.2, "The mapping definition at `my_module.settings:foobar` is invalid: its `invalid` key contains a double. It must be an array."]
   *           [true, "type", "The mapping definition at `my_module.settings:foobar` is invalid: its `invalid` key contains a string. It must be an array."]
   *           [true, false, "The mapping definition at `my_module.settings:foobar` is invalid: its `invalid` key contains a boolean. It must be an array."]
   */
  public function testInvalidMappingKeyDefinition(bool $has_parent, mixed $invalid_key_definition, string $expected_message): void {
    $definition = new MapDataDefinition([
      'type' => 'mapping',
      'mapping' => [
        'valid' => [
          'type' => 'boolean',
          'label' => 'This is a valid key-value pair in this mapping',
        ],
        'invalid' => $invalid_key_definition,
      ],
    ]);
    $parent = NULL;
    if ($has_parent) {
      $parent = new Mapping(
        new MapDataDefinition(['type' => 'mapping', 'mapping' => []]),
        'my_module.settings',
      );
    }
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($expected_message);
    new Mapping($definition, 'foobar', $parent);
  }

  /**
   * @testWith [true]
   *           [1]
   *           ["true"]
   *           [0]
   *           ["false"]
   */
  public function testInvalidRequiredKeyFlag(mixed $required_key_flag_value): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The `requiredKey` flag must either be omitted or have `false` as the value.');
    new Mapping(new MapDataDefinition([
      'type' => 'mapping',
      'mapping' => [
        'something' => [
          'requiredKey' => $required_key_flag_value,
        ],
      ],
    ]));
  }

}
