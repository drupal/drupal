<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\Schema\ConfigSchemaAlterException;
use Drupal\Core\Config\Schema\Ignore;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\Undefined;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\image\ImageEffectInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests schema for configuration objects.
 *
 * @group config
 */
class ConfigSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'language',
    'field',
    'image',
    'config_test',
    'config_schema_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'image', 'config_schema_test']);
  }

  /**
   * Tests the basic metadata retrieval layer.
   */
  public function testSchemaMapping(): void {
    // Nonexistent configuration key will have Undefined as metadata.
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.no_such_key'));
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.no_such_key');
    $expected = [];
    $expected['label'] = 'Undefined';
    $expected['class'] = Undefined::class;
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for nonexistent configuration.');

    // Configuration file without schema will return Undefined as well.
    $this->assertFalse(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.no_schema'));
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.no_schema');
    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for configuration with no schema.');

    // Configuration file with only some schema.
    $this->assertTrue(\Drupal::service('config.typed')->hasConfigSchema('config_schema_test.some_schema'));
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.some_schema');
    $expected = [];
    $expected['label'] = 'Schema test data';
    $expected['class'] = Mapping::class;
    $expected['mapping']['langcode']['type'] = 'langcode';
    $expected['mapping']['langcode']['requiredKey'] = FALSE;
    $expected['mapping']['_core']['type'] = '_core_config_info';
    $expected['mapping']['_core']['requiredKey'] = FALSE;
    $expected['mapping']['test_item'] = ['label' => 'Test item'];
    $expected['mapping']['test_list'] = ['label' => 'Test list'];
    $expected['type'] = 'config_schema_test.some_schema';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'LangcodeRequiredIfTranslatableValues' => NULL,
    ];
    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for configuration with only some schema.');

    // Check type detection on elements with undefined types.
    $config = \Drupal::service('config.typed')->get('config_schema_test.some_schema');
    $definition = $config->get('test_item')->getDataDefinition()->toArray();
    $expected = [];
    $expected['label'] = 'Test item';
    $expected['class'] = Undefined::class;
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['requiredKey'] = TRUE;
    $this->assertEquals($expected, $definition, 'Automatic type detected for a scalar is undefined.');
    $definition = $config->get('test_list')->getDataDefinition()->toArray();
    $expected = [];
    $expected['label'] = 'Test list';
    $expected['class'] = Undefined::class;
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['requiredKey'] = TRUE;
    $this->assertEquals($expected, $definition, 'Automatic type detected for a list is undefined.');
    $definition = $config->get('test_no_schema')->getDataDefinition()->toArray();
    $expected = [];
    $expected['label'] = 'Undefined';
    $expected['class'] = Undefined::class;
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $this->assertEquals($expected, $definition, 'Automatic type detected for an undefined integer is undefined.');

    // Simple case, straight metadata.
    $definition = \Drupal::service('config.typed')->getDefinition('system.maintenance');
    $expected = [];
    $expected['label'] = 'Maintenance mode';
    $expected['class'] = Mapping::class;
    $expected['mapping']['message'] = [
      'label' => 'Message to display when in maintenance mode',
      'type' => 'text',
    ];
    $expected['mapping']['langcode'] = [
      'type' => 'langcode',
    ];
    $expected['mapping']['langcode']['requiredKey'] = FALSE;
    $expected['mapping']['_core']['type'] = '_core_config_info';
    $expected['mapping']['_core']['requiredKey'] = FALSE;
    $expected['type'] = 'system.maintenance';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'FullyValidatable' => NULL,
      'LangcodeRequiredIfTranslatableValues' => NULL,
    ];
    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for system.maintenance');

    // Mixed schema with ignore elements.
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.ignore');
    $expected = [];
    $expected['label'] = 'Ignore test';
    $expected['class'] = Mapping::class;
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['mapping']['langcode'] = [
      'type' => 'langcode',
    ];
    $expected['mapping']['langcode']['requiredKey'] = FALSE;
    $expected['mapping']['_core']['type'] = '_core_config_info';
    $expected['mapping']['_core']['requiredKey'] = FALSE;
    $expected['mapping']['label'] = [
      'label' => 'Label',
      'type' => 'label',
    ];
    $expected['mapping']['irrelevant'] = [
      'label' => 'Irrelevant',
      'type' => 'ignore',
    ];
    $expected['mapping']['indescribable'] = [
      'label' => 'Indescribable',
      'type' => 'ignore',
    ];
    $expected['mapping']['weight'] = [
      'label' => 'Weight',
      'type' => 'weight',
    ];
    $expected['type'] = 'config_schema_test.ignore';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'LangcodeRequiredIfTranslatableValues' => NULL,
    ];

    $this->assertEquals($expected, $definition);

    // The ignore elements themselves.
    $definition = \Drupal::service('config.typed')->get('config_schema_test.ignore')->get('irrelevant')->getDataDefinition()->toArray();
    $expected = [];
    $expected['type'] = 'ignore';
    $expected['label'] = 'Irrelevant';
    $expected['class'] = Ignore::class;
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['requiredKey'] = TRUE;
    $this->assertEquals($expected, $definition);
    $definition = \Drupal::service('config.typed')->get('config_schema_test.ignore')->get('indescribable')->getDataDefinition()->toArray();
    $expected['label'] = 'Indescribable';
    $this->assertEquals($expected, $definition);

    // More complex case, generic type. Metadata for image style.
    $definition = \Drupal::service('config.typed')->getDefinition('image.style.large');
    $expected = [];
    $expected['label'] = 'Image style';
    $expected['class'] = Mapping::class;
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['mapping']['name']['type'] = 'machine_name';
    $expected['mapping']['uuid']['type'] = 'uuid';
    $expected['mapping']['uuid']['label'] = 'UUID';
    $expected['mapping']['langcode']['type'] = 'langcode';
    $expected['mapping']['status']['type'] = 'boolean';
    $expected['mapping']['status']['label'] = 'Status';
    $expected['mapping']['dependencies']['type'] = 'config_dependencies';
    $expected['mapping']['dependencies']['label'] = 'Dependencies';
    $expected['mapping']['label']['type'] = 'required_label';
    $expected['mapping']['label']['label'] = 'Label';
    $expected['mapping']['effects']['type'] = 'sequence';
    $expected['mapping']['effects']['sequence']['type'] = 'mapping';
    $expected['mapping']['effects']['sequence']['mapping']['id']['type'] = 'string';
    $expected['mapping']['effects']['sequence']['mapping']['id']['constraints'] = [
      'PluginExists' => [
        'manager' => 'plugin.manager.image.effect',
        'interface' => ImageEffectInterface::class,
      ],
    ];
    $expected['mapping']['effects']['sequence']['mapping']['data']['type'] = 'image.effect.[%parent.id]';
    $expected['mapping']['effects']['sequence']['mapping']['weight']['type'] = 'weight';
    $expected['mapping']['effects']['sequence']['mapping']['uuid']['type'] = 'uuid';
    $expected['mapping']['third_party_settings']['type'] = 'sequence';
    $expected['mapping']['third_party_settings']['label'] = 'Third party settings';
    $expected['mapping']['third_party_settings']['sequence']['type'] = '[%parent.%parent.%type].third_party.[%key]';
    $expected['mapping']['third_party_settings']['requiredKey'] = FALSE;
    $expected['mapping']['_core']['type'] = '_core_config_info';
    $expected['mapping']['_core']['requiredKey'] = FALSE;
    $expected['type'] = 'image.style.*';
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'FullyValidatable' => NULL,
    ];

    $this->assertEquals($expected, $definition);

    // More complex, type based on a complex one.
    $definition = \Drupal::service('config.typed')->getDefinition('image.effect.image_scale');
    // This should be the schema for image.effect.image_scale.
    $expected = [];
    $expected['label'] = 'Image scale';
    $expected['class'] = Mapping::class;
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['mapping']['width']['type'] = 'integer';
    $expected['mapping']['width']['label'] = 'Width';
    $expected['mapping']['width']['nullable'] = TRUE;
    $expected['mapping']['width']['constraints'] = ['NotBlank' => ['allowNull' => TRUE]];
    $expected['mapping']['height']['type'] = 'integer';
    $expected['mapping']['height']['label'] = 'Height';
    $expected['mapping']['height']['nullable'] = TRUE;
    $expected['mapping']['height']['constraints'] = ['NotBlank' => ['allowNull' => TRUE]];
    $expected['mapping']['upscale']['type'] = 'boolean';
    $expected['mapping']['upscale']['label'] = 'Upscale';
    $expected['type'] = 'image.effect.image_scale';
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'FullyValidatable' => NULL,
    ];

    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for image.effect.image_scale');

    // Most complex case, get metadata for actual configuration element.
    $effects = \Drupal::service('config.typed')->get('image.style.medium')->get('effects');
    $definition = $effects->get('bddf0d06-42f9-4c75-a700-a33cafa25ea0')->get('data')->getDataDefinition()->toArray();
    // This should be the schema for image.effect.image_scale, reuse previous
    // one.
    $expected['type'] = 'image.effect.image_scale';
    $expected['mapping']['width']['requiredKey'] = TRUE;
    $expected['mapping']['height']['requiredKey'] = TRUE;
    $expected['mapping']['upscale']['requiredKey'] = TRUE;
    $expected['requiredKey'] = TRUE;
    $expected['required'] = TRUE;

    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for the first effect of image.style.medium');

    $test = \Drupal::service('config.typed')->get('config_test.dynamic.third_party')->get('third_party_settings.config_schema_test');
    $definition = $test->getDataDefinition()->toArray();
    $expected = [];
    $expected['type'] = 'config_test.dynamic.*.third_party.config_schema_test';
    $expected['label'] = 'Mapping';
    $expected['class'] = Mapping::class;
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['mapping'] = [
      'integer' => ['type' => 'integer', 'requiredKey' => TRUE],
      'string' => ['type' => 'string', 'requiredKey' => TRUE],
    ];
    $expected['constraints'] = ['ValidKeys' => '<infer>'];
    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for config_test.dynamic.third_party:third_party_settings.config_schema_test');

    // More complex, several level deep test.
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.some_schema.some_module.section_one.subsection');
    // This should be the schema of
    // config_schema_test.some_schema.some_module.*.*.
    $expected = [];
    $expected['label'] = 'Schema multiple filesystem marker test';
    $expected['class'] = Mapping::class;
    $expected['mapping']['langcode']['type'] = 'langcode';
    $expected['mapping']['langcode']['requiredKey'] = FALSE;
    $expected['mapping']['_core']['type'] = '_core_config_info';
    $expected['mapping']['_core']['requiredKey'] = FALSE;
    $expected['mapping']['test_id']['type'] = 'string';
    $expected['mapping']['test_id']['label'] = 'ID';
    $expected['mapping']['test_description']['type'] = 'text';
    $expected['mapping']['test_description']['label'] = 'Description';
    $expected['type'] = 'config_schema_test.some_schema.some_module.*.*';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'LangcodeRequiredIfTranslatableValues' => NULL,
    ];

    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for config_schema_test.some_schema.some_module.section_one.subsection');

    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.some_schema.some_module.section_two.subsection');
    // The other file should have the same schema.
    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for config_schema_test.some_schema.some_module.section_two.subsection');
  }

  /**
   * Tests metadata retrieval with several levels of %parent indirection.
   */
  public function testSchemaMappingWithParents(): void {
    $config_data = \Drupal::service('config.typed')->get('config_schema_test.some_schema.with_parents');

    // Test fetching parent one level up.
    $entry = $config_data->get('one_level');
    $definition = $entry->get('test_item')->getDataDefinition()->toArray();
    $expected = [
      'type' => 'config_schema_test.some_schema.with_parents.key_1',
      'label' => 'Test item nested one level',
      'class' => StringData::class,
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
      'unwrap_for_canonical_representation' => TRUE,
      'requiredKey' => TRUE,
    ];
    $this->assertEquals($expected, $definition);

    // Test fetching parent two levels up.
    $entry = $config_data->get('two_levels');
    $definition = $entry->get('wrapper')->get('test_item')->getDataDefinition()->toArray();
    $expected = [
      'type' => 'config_schema_test.some_schema.with_parents.key_2',
      'label' => 'Test item nested two levels',
      'class' => StringData::class,
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
      'unwrap_for_canonical_representation' => TRUE,
      'requiredKey' => TRUE,
    ];
    $this->assertEquals($expected, $definition);

    // Test fetching parent three levels up.
    $entry = $config_data->get('three_levels');
    $definition = $entry->get('wrapper_1')->get('wrapper_2')->get('test_item')->getDataDefinition()->toArray();
    $expected = [
      'type' => 'config_schema_test.some_schema.with_parents.key_3',
      'label' => 'Test item nested three levels',
      'class' => StringData::class,
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
      'unwrap_for_canonical_representation' => TRUE,
      'requiredKey' => TRUE,
    ];
    $this->assertEquals($expected, $definition);
  }

  /**
   * Tests metadata applied to configuration objects.
   */
  public function testSchemaData(): void {
    // Try a simple property.
    $meta = \Drupal::service('config.typed')->get('system.site');
    $property = $meta->get('page')->get('front');
    $this->assertInstanceOf(StringInterface::class, $property);
    $this->assertEquals('/user/login', $property->getValue(), 'Got the right value for page.front data.');
    $definition = $property->getDataDefinition();
    $this->assertEmpty($definition['translatable'], 'Got the right translatability setting for page.front data.');

    // Check nested array of properties.
    $list = $meta->get('page')->getElements();
    $this->assertCount(3, $list, 'Got a list with the right number of properties for site page data');
    $this->assertArrayHasKey('front', $list);
    $this->assertArrayHasKey('403', $list);
    $this->assertArrayHasKey('404', $list);
    $this->assertEquals('/user/login', $list['front']->getValue(), 'Got the right value for page.front data from the list.');

    // And test some TypedConfigInterface methods.
    $properties = $list;
    $this->assertCount(3, $properties, 'Got the right number of properties for site page.');
    $this->assertSame($list['front'], $properties['front']);
    $values = $meta->get('page')->toArray();
    $this->assertCount(3, $values, 'Got the right number of property values for site page.');
    $this->assertSame($values['front'], '/user/login');

    // Now let's try something more complex, with nested objects.
    $wrapper = \Drupal::service('config.typed')->get('image.style.large');
    $effects = $wrapper->get('effects');
    $this->assertCount(2, $effects->toArray(), 'Got an array with effects for image.style.large data');
    foreach ($effects->toArray() as $uuid => $definition) {
      $effect = $effects->get($uuid)->getElements();
      if ($definition['id'] == 'image_scale') {
        $this->assertFalse($effect['data']->isEmpty(), 'Got data for the image scale effect from metadata.');
        $this->assertSame('image_scale', $effect['id']->getValue(), 'Got data for the image scale effect from metadata.');
        $this->assertInstanceOf(IntegerInterface::class, $effect['data']->get('width'));
        $this->assertEquals(480, $effect['data']->get('width')->getValue(), 'Got the right value for the scale effect width.');
      }
      if ($definition['id'] == 'image_convert') {
        $this->assertFalse($effect['data']->isEmpty(), 'Got data for the image convert effect from metadata.');
        $this->assertSame('image_convert', $effect['id']->getValue(), 'Got data for the image convert effect from metadata.');
        $this->assertSame('webp', $effect['data']->get('extension')->getValue(), 'Got the right value for the convert effect extension.');
      }
    }
  }

  /**
   * Tests configuration value data type enforcement using schemas.
   */
  public function testConfigSaveWithSchema(): void {
    $untyped_values = [
      // Test a custom type.
      'config_schema_test_integer' => '1',
      'config_schema_test_integer_empty_string' => '',
      'integer' => '100',
      'null_integer' => '',
      'float' => '3.14',
      'null_float' => '',
      'string' => 1,
      'null_string' => NULL,
      'empty_string' => '',
      'boolean' => 1,
      // If the config schema doesn't have a type it shouldn't be casted.
      'no_type' => 1,
      'mapping' => [
        'string' => 1,
      ],
      'sequence' => [1, 0, 1],
      // Not in schema and therefore should be left untouched.
      'not_present_in_schema' => TRUE,
    ];

    $untyped_to_typed = $untyped_values;

    $typed_values = [
      'config_schema_test_integer' => 1,
      'config_schema_test_integer_empty_string' => NULL,
      'integer' => 100,
      'null_integer' => NULL,
      'float' => 3.14,
      'null_float' => NULL,
      'string' => '1',
      'null_string' => NULL,
      'empty_string' => '',
      'boolean' => TRUE,
      'no_type' => 1,
      'mapping' => [
        'string' => '1',
      ],
      'sequence' => [TRUE, FALSE, TRUE],
      'not_present_in_schema' => TRUE,
    ];

    // Save config which has a schema that enforces types.
    $this->config('config_schema_test.schema_data_types')
      ->setData($untyped_to_typed)
      ->save();
    $this->assertSame($typed_values, $this->config('config_schema_test.schema_data_types')->get());

    // Save config which does not have a schema that enforces types.
    $this->config('config_schema_test.no_schema_data_types')
      ->setData($untyped_values)
      ->save();
    $this->assertSame($untyped_values, $this->config('config_schema_test.no_schema_data_types')->get());

    // Ensure that configuration objects with keys marked as ignored are not
    // changed when saved. The 'config_schema_test.ignore' will have been saved
    // during the installation of configuration in the setUp method.
    $extension_path = __DIR__ . '/../../../../../modules/config/tests/config_schema_test/';
    $install_storage = new FileStorage($extension_path . InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $original_data = $install_storage->read('config_schema_test.ignore');
    $installed_data = $this->config('config_schema_test.ignore')->get();
    unset($installed_data['_core']);
    $this->assertSame($original_data, $installed_data);
  }

  /**
   * Test configuration value data type enforcement using schemas.
   */
  public function testConfigSaveMappingSort(): void {
    // Top level map sorting.
    $data = [
      'foo' => '1',
      'bar' => '2',
    ];
    // Save config which has a schema that enforces types.
    $this->config('config_schema_test.schema_mapping_sort')
      ->setData($data)
      ->save();
    $this->assertSame(['bar' => '2', 'foo' => '1'], $this->config('config_schema_test.schema_mapping_sort')->get());
    $this->config('config_schema_test.schema_mapping_sort')->set('map', ['sub_bar' => '2', 'sub_foo' => '1'])->save();
    $this->assertSame(['sub_foo' => '1', 'sub_bar' => '2'], $this->config('config_schema_test.schema_mapping_sort')->get('map'));
  }

  /**
   * Tests configuration sequence sorting using schemas.
   */
  public function testConfigSaveWithSequenceSorting(): void {
    $data = [
      'keyed_sort' => [
        'b' => '1',
        'a' => '2',
      ],
      'no_sort' => [
        'b' => '2',
        'a' => '1',
      ],
    ];
    // Save config which has a schema that enforces sorting.
    $this->config('config_schema_test.schema_sequence_sort')
      ->setData($data)
      ->save();
    $this->assertSame(['a' => '2', 'b' => '1'], $this->config('config_schema_test.schema_sequence_sort')->get('keyed_sort'));
    $this->assertSame(['b' => '2', 'a' => '1'], $this->config('config_schema_test.schema_sequence_sort')->get('no_sort'));

    $data = [
      'value_sort' => ['b', 'a'],
      'no_sort' => ['b', 'a'],
    ];
    // Save config which has a schema that enforces sorting.
    $this->config('config_schema_test.schema_sequence_sort')
      ->setData($data)
      ->save();

    $this->assertSame(['a', 'b'], $this->config('config_schema_test.schema_sequence_sort')->get('value_sort'));
    $this->assertSame(['b', 'a'], $this->config('config_schema_test.schema_sequence_sort')->get('no_sort'));

    // Value sort does not preserve keys - this is intentional.
    $data = [
      'value_sort' => [1 => 'b', 2 => 'a'],
      'no_sort' => [1 => 'b', 2 => 'a'],
    ];
    // Save config which has a schema that enforces sorting.
    $this->config('config_schema_test.schema_sequence_sort')
      ->setData($data)
      ->save();

    $this->assertSame(['a', 'b'], $this->config('config_schema_test.schema_sequence_sort')->get('value_sort'));
    $this->assertSame([1 => 'b', 2 => 'a'], $this->config('config_schema_test.schema_sequence_sort')->get('no_sort'));

    // Test sorts do not destroy complex values.
    $data = [
      'complex_sort_value' => [['foo' => 'b', 'bar' => 'b'] , ['foo' => 'a', 'bar' => 'a']],
      'complex_sort_key' => ['b' => ['foo' => '1', 'bar' => '1'] , 'a' => ['foo' => '2', 'bar' => '2']],
    ];
    $this->config('config_schema_test.schema_sequence_sort')
      ->setData($data)
      ->save();
    $this->assertSame([['foo' => 'a', 'bar' => 'a'], ['foo' => 'b', 'bar' => 'b']], $this->config('config_schema_test.schema_sequence_sort')->get('complex_sort_value'));
    $this->assertSame(['a' => ['foo' => '2', 'bar' => '2'], 'b' => ['foo' => '1', 'bar' => '1']], $this->config('config_schema_test.schema_sequence_sort')->get('complex_sort_key'));

    // Swap the previous test scenario around.
    $data = [
      'complex_sort_value' => ['b' => ['foo' => '1', 'bar' => '1'] , 'a' => ['foo' => '2', 'bar' => '2']],
      'complex_sort_key' => [['foo' => 'b', 'bar' => 'b'] , ['foo' => 'a', 'bar' => 'a']],
    ];
    $this->config('config_schema_test.schema_sequence_sort')
      ->setData($data)
      ->save();
    $this->assertSame([['foo' => '1', 'bar' => '1'], ['foo' => '2', 'bar' => '2']], $this->config('config_schema_test.schema_sequence_sort')->get('complex_sort_value'));
    $this->assertSame([['foo' => 'b', 'bar' => 'b'], ['foo' => 'a', 'bar' => 'a']], $this->config('config_schema_test.schema_sequence_sort')->get('complex_sort_key'));

  }

  /**
   * Tests fallback to a greedy wildcard.
   */
  public function testSchemaFallback(): void {
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.wildcard_fallback.something');
    // This should be the schema of config_schema_test.wildcard_fallback.*.
    $expected = [];
    $expected['label'] = 'Schema wildcard fallback test';
    $expected['class'] = Mapping::class;
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['unwrap_for_canonical_representation'] = TRUE;
    $expected['mapping']['langcode']['type'] = 'langcode';
    $expected['mapping']['langcode']['requiredKey'] = FALSE;
    $expected['mapping']['_core']['type'] = '_core_config_info';
    $expected['mapping']['_core']['requiredKey'] = FALSE;
    $expected['mapping']['test_id']['type'] = 'string';
    $expected['mapping']['test_id']['label'] = 'ID';
    $expected['mapping']['test_description']['type'] = 'text';
    $expected['mapping']['test_description']['label'] = 'Description';
    $expected['type'] = 'config_schema_test.wildcard_fallback.*';
    $expected['constraints'] = [
      'ValidKeys' => '<infer>',
      'LangcodeRequiredIfTranslatableValues' => NULL,
    ];

    $this->assertEquals($expected, $definition, 'Retrieved the right metadata for config_schema_test.wildcard_fallback.something');

    $definition2 = \Drupal::service('config.typed')->getDefinition('config_schema_test.wildcard_fallback.something.something');
    // This should be the schema of config_schema_test.wildcard_fallback.* as
    // well.
    $this->assertSame($definition, $definition2);
  }

  /**
   * Tests use of colons in schema type determination.
   *
   * @see \Drupal\Core\Config\TypedConfigManager::getFallbackName()
   */
  public function testColonsInSchemaTypeDetermination(): void {
    $tests = \Drupal::service('config.typed')->get('config_schema_test.plugin_types')->get('tests')->getElements();
    $definition = $tests[0]->getDataDefinition()->toArray();
    $this->assertEquals('test.plugin_types.boolean', $definition['type']);

    $definition = $tests[1]->getDataDefinition()->toArray();
    $this->assertEquals('test.plugin_types.boolean:*', $definition['type']);

    $definition = $tests[2]->getDataDefinition()->toArray();
    $this->assertEquals('test.plugin_types.*', $definition['type']);

    $definition = $tests[3]->getDataDefinition()->toArray();
    $this->assertEquals('test.plugin_types.*', $definition['type']);

    $tests = \Drupal::service('config.typed')->get('config_schema_test.plugin_types')->get('test_with_parents')->getElements();
    $definition = $tests[0]->get('settings')->getDataDefinition()->toArray();
    $this->assertEquals('test_with_parents.plugin_types.boolean', $definition['type']);

    $definition = $tests[1]->get('settings')->getDataDefinition()->toArray();
    $this->assertEquals('test_with_parents.plugin_types.boolean:*', $definition['type']);

    $definition = $tests[2]->get('settings')->getDataDefinition()->toArray();
    $this->assertEquals('test_with_parents.plugin_types.*', $definition['type']);

    $definition = $tests[3]->get('settings')->getDataDefinition()->toArray();
    $this->assertEquals('test_with_parents.plugin_types.*', $definition['type']);
  }

  /**
   * Tests hook_config_schema_info_alter().
   */
  public function testConfigSchemaInfoAlter(): void {
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = \Drupal::service('config.typed');
    $typed_config->clearCachedDefinitions();

    // Ensure that keys can not be added or removed by
    // hook_config_schema_info_alter().
    \Drupal::state()->set('config_schema_test_exception_remove', TRUE);
    try {
      $typed_config->getDefinitions();
      $this->fail('Expected ConfigSchemaAlterException thrown.');
    }
    catch (ConfigSchemaAlterException $e) {
      $this->assertEquals('Invoking hook_config_schema_info_alter() has removed (config_schema_test.hook) schema definitions', $e->getMessage());
    }

    \Drupal::state()->set('config_schema_test_exception_add', TRUE);
    try {
      $typed_config->getDefinitions();
      $this->fail('Expected ConfigSchemaAlterException thrown.');
    }
    catch (ConfigSchemaAlterException $e) {
      $this->assertEquals('Invoking hook_config_schema_info_alter() has added (config_schema_test.hook_added_definition) and removed (config_schema_test.hook) schema definitions', $e->getMessage());
    }

    \Drupal::state()->set('config_schema_test_exception_remove', FALSE);
    try {
      $typed_config->getDefinitions();
      $this->fail('Expected ConfigSchemaAlterException thrown.');
    }
    catch (ConfigSchemaAlterException $e) {
      $this->assertEquals('Invoking hook_config_schema_info_alter() has added (config_schema_test.hook_added_definition) schema definitions', $e->getMessage());
    }

    // Tests that hook_config_schema_info_alter() can add additional metadata to
    // existing configuration schema.
    \Drupal::state()->set('config_schema_test_exception_add', FALSE);
    $definitions = $typed_config->getDefinitions();
    $this->assertEquals('new schema info', $definitions['config_schema_test.hook']['additional_metadata']);
  }

  /**
   * Tests saving config when the type is wrapped by a dynamic type.
   */
  public function testConfigSaveWithWrappingSchema(): void {
    $untyped_values = [
      'tests' => [
        [
          'wrapper_value' => 'foo',
          'plugin_id' => 'wrapper:foo',
          'internal_value' => 100,
        ],
      ],
    ];

    $typed_values = [
      'tests' => [
        [
          'plugin_id' => 'wrapper:foo',
          'internal_value' => '100',
          'wrapper_value' => 'foo',
        ],
      ],
    ];

    // Save config which has a schema that enforces types.
    \Drupal::configFactory()->getEditable('wrapping.config_schema_test.plugin_types')
      ->setData($untyped_values)
      ->save();
    $this->assertSame($typed_values, \Drupal::config('wrapping.config_schema_test.plugin_types')->get());
  }

  /**
   * Tests dynamic config schema type with multiple sub-key references.
   */
  public function testConfigSaveWithWrappingSchemaDoubleBrackets(): void {
    $untyped_values = [
      'tests' => [
        [
          'wrapper_value' => 'foo',
          'foo' => 'turtle',
          'bar' => 'horse',
          // Converted to a string by 'test.double_brackets.turtle.horse'
          // schema.
          'another_key' => '100',
        ],
      ],
    ];

    $typed_values = [
      'tests' => [
        [
          'another_key' => 100,
          'foo' => 'turtle',
          'bar' => 'horse',
          'wrapper_value' => 'foo',
        ],
      ],
    ];

    // Save config which has a schema that enforces types.
    \Drupal::configFactory()->getEditable('wrapping.config_schema_test.double_brackets')
      ->setData($untyped_values)
      ->save();
    // TRICKY: https://www.drupal.org/project/drupal/issues/2663410 introduced a
    // bug that made TypedConfigManager sensitive to cache pollution. Saving
    // config triggers validation, which in turn triggers that cache pollution
    // bug. This is a work-around.
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3400181
    \Drupal::service('config.typed')->clearCachedDefinitions();
    $this->assertSame($typed_values, \Drupal::config('wrapping.config_schema_test.double_brackets')->get());

    $tests = \Drupal::service('config.typed')->get('wrapping.config_schema_test.double_brackets')->get('tests')->getElements();
    $definition = $tests[0]->getDataDefinition()->toArray();
    $this->assertEquals('wrapping.test.double_brackets.*||test.double_brackets.turtle.horse', $definition['type']);

    $untyped_values = [
      'tests' => [
        [
          'wrapper_value' => 'foo',
          'foo' => 'cat',
          'bar' => 'dog',
          // Converted to a string by 'test.double_brackets.cat.dog' schema.
          'another_key' => 100,
        ],
      ],
    ];

    $typed_values = [
      'tests' => [
        [
          'another_key' => '100',
          'foo' => 'cat',
          'bar' => 'dog',
          'wrapper_value' => 'foo',
        ],
      ],
    ];

    // Save config which has a schema that enforces types.
    \Drupal::configFactory()->getEditable('wrapping.config_schema_test.double_brackets')
      ->setData($untyped_values)
      ->save();
    // TRICKY: https://www.drupal.org/project/drupal/issues/2663410 introduced a
    // bug that made TypedConfigManager sensitive to cache pollution. Saving
    // config in a test triggers the schema checking and validation logic from
    // \Drupal\Core\Config\Development\ConfigSchemaChecker , which in turn
    // triggers that cache pollution bug. This is a work-around.
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3400181
    \Drupal::service('config.typed')->clearCachedDefinitions();
    $this->assertSame($typed_values, \Drupal::config('wrapping.config_schema_test.double_brackets')->get());

    $tests = \Drupal::service('config.typed')->get('wrapping.config_schema_test.double_brackets')->get('tests')->getElements();
    $definition = $tests[0]->getDataDefinition()->toArray();
    $this->assertEquals('wrapping.test.double_brackets.*||test.double_brackets.cat.dog', $definition['type']);

    // Combine everything in a single save.
    $typed_values = [
      'tests' => [
        [
          'another_key' => 100,
          'foo' => 'cat',
          'bar' => 'dog',
          'wrapper_value' => 'foo',
        ],
        [
          'another_key' => '100',
          'foo' => 'turtle',
          'bar' => 'horse',
          'wrapper_value' => 'foo',
        ],
      ],
    ];
    \Drupal::configFactory()->getEditable('wrapping.config_schema_test.double_brackets')
      ->setData($typed_values)
      ->save();
    // TRICKY: https://www.drupal.org/project/drupal/issues/2663410 introduced a
    // bug that made TypedConfigManager sensitive to cache pollution. Saving
    // config in a test triggers the schema checking and validation logic from
    // \Drupal\Core\Config\Development\ConfigSchemaChecker , which in turn
    // triggers that cache pollution bug. This is a work-around.
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3400181
    \Drupal::service('config.typed')->clearCachedDefinitions();
    $tests = \Drupal::service('config.typed')->get('wrapping.config_schema_test.double_brackets')->get('tests')->getElements();
    $definition = $tests[0]->getDataDefinition()->toArray();
    $this->assertEquals('wrapping.test.double_brackets.*||test.double_brackets.cat.dog', $definition['type']);
    $definition = $tests[1]->getDataDefinition()->toArray();
    $this->assertEquals('wrapping.test.double_brackets.*||test.double_brackets.turtle.horse', $definition['type']);

    $typed_values = [
      'tests' => [
        [
          'id' => 'cat:persian.dog',
          'foo' => 'cat',
          'bar' => 'dog',
          'breed' => 'persian',
        ],
      ],
    ];

    \Drupal::configFactory()->getEditable('wrapping.config_schema_test.other_double_brackets')
      ->setData($typed_values)
      ->save();
    // TRICKY: https://www.drupal.org/project/drupal/issues/2663410 introduced a
    // bug that made TypedConfigManager sensitive to cache pollution. Saving
    // config in a test triggers the schema checking and validation logic from
    // \Drupal\Core\Config\Development\ConfigSchemaChecker , which in turn
    // triggers that cache pollution bug. This is a work-around.
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3400181
    \Drupal::service('config.typed')->clearCachedDefinitions();
    $tests = \Drupal::service('config.typed')->get('wrapping.config_schema_test.other_double_brackets')->get('tests')->getElements();
    $definition = $tests[0]->getDataDefinition()->toArray();
    // Check that definition type is a merge of the expected types.
    $this->assertEquals('wrapping.test.other_double_brackets.*||test.double_brackets.cat:*.*', $definition['type']);
    // Check that breed was inherited from parent definition.
    $this->assertEquals([
      'type' => 'string',
      'requiredKey' => TRUE,
    ], $definition['mapping']['breed']);
  }

  /**
   * Tests exception is thrown for the root object.
   */
  public function testLangcodeRequiredIfTranslatableValuesConstraintError(): void {
    $config = \Drupal::configFactory()->getEditable('config_test.foo');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The LangcodeRequiredIfTranslatableValues constraint is applied to \'config_test.foo::broken_langcode_required\'. This constraint can only operate on the root object being validated.');

    $config
      ->set('broken_langcode_required.foo', 'bar')
      ->save();
  }

}
