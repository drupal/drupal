<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigSchemaTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests schema for configuration objects.
 *
 * @group config
 */
class ConfigSchemaTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'language', 'locale', 'field', 'image', 'config_schema_test');

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system', 'image', 'config_schema_test'));
  }

  /**
   * Tests the basic metadata retrieval layer.
   */
  function testSchemaMapping() {
    // Nonexistent configuration key will have Undefined as metadata.
    $this->assertIdentical(FALSE, \Drupal::service('config.typed')->hasConfigSchema('config_schema_test.no_such_key'));
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.no_such_key');
    $expected = array();
    $expected['label'] = 'Undefined';
    $expected['class'] = '\Drupal\Core\Config\Schema\Undefined';
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for nonexistent configuration.');

    // Configuration file without schema will return Undefined as well.
    $this->assertIdentical(FALSE, \Drupal::service('config.typed')->hasConfigSchema('config_schema_test.noschema'));
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.noschema');
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for configuration with no schema.');

    // Configuration file with only some schema.
    $this->assertIdentical(TRUE, \Drupal::service('config.typed')->hasConfigSchema('config_schema_test.someschema'));
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.someschema');
    $expected = array();
    $expected['label'] = 'Schema test data';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['testitem'] = array('label' => 'Test item');
    $expected['mapping']['testlist'] = array('label' => 'Test list');
    $expected['type'] = 'config_schema_test.someschema';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for configuration with only some schema.');

    // Check type detection on elements with undefined types.
    $config = \Drupal::service('config.typed')->get('config_schema_test.someschema');
    $definition = $config['testitem']->getDataDefinition()->toArray();
    $expected = array();
    $expected['label'] = 'Test item';
    $expected['class'] = '\Drupal\Core\Config\Schema\Undefined';
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $this->assertEqual($definition, $expected, 'Automatic type detected for a scalar is undefined.');
    $definition = $config['testlist']->getDataDefinition()->toArray();
    $expected = array();
    $expected['label'] = 'Test list';
    $expected['class'] = '\Drupal\Core\Config\Schema\Undefined';
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $this->assertEqual($definition, $expected, 'Automatic type detected for a list is undefined.');
    $definition = $config['testnoschema']->getDataDefinition()->toArray();
    $expected = array();
    $expected['label'] = 'Undefined';
    $expected['class'] = '\Drupal\Core\Config\Schema\Undefined';
    $expected['type'] = 'undefined';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $this->assertEqual($definition, $expected, 'Automatic type detected for an undefined integer is undefined.');

    // Simple case, straight metadata.
    $definition = \Drupal::service('config.typed')->getDefinition('system.maintenance');
    $expected = array();
    $expected['label'] = 'Maintenance mode';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['message'] = array(
      'label' =>  'Message to display when in maintenance mode',
      'type' => 'text',
    );
    $expected['mapping']['langcode'] = array(
      'label' => 'Default language',
      'type' => 'string',
    );
    $expected['type'] = 'system.maintenance';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for system.maintenance');

    // Mixed schema with ignore elements.
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.ignore');
    $expected = array();
    $expected['label'] = 'Ignore test';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['mapping']['label'] = array(
      'label' =>  'Label',
      'type' => 'label',
    );
    $expected['mapping']['irrelevant'] = array(
      'label' => 'Irrelevant',
      'type' => 'ignore',
    );
    $expected['mapping']['indescribable'] = array(
      'label' => 'Indescribable',
      'type' => 'ignore',
    );
    $expected['mapping']['weight'] = array(
      'label' => 'Weight',
      'type' => 'integer',
    );
    $expected['type'] = 'config_schema_test.ignore';

    $this->assertEqual($definition, $expected);

    // The ignore elements themselves.
    $definition = \Drupal::service('config.typed')->get('config_schema_test.ignore')->get('irrelevant')->getDataDefinition()->toArray();
    $expected = array();
    $expected['type'] = 'ignore';
    $expected['label'] = 'Irrelevant';
    $expected['class'] = '\Drupal\Core\Config\Schema\Ignore';
    $expected['definition_class'] = '\Drupal\Core\TypedData\DataDefinition';
    $this->assertEqual($definition, $expected);
    $definition = \Drupal::service('config.typed')->get('config_schema_test.ignore')->get('indescribable')->getDataDefinition()->toArray();
    $expected['label'] = 'Indescribable';
    $this->assertEqual($definition, $expected);

    // More complex case, generic type. Metadata for image style.
    $definition = \Drupal::service('config.typed')->getDefinition('image.style.large');
    $expected = array();
    $expected['label'] = 'Image style';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['mapping']['name']['type'] = 'string';
    $expected['mapping']['uuid']['type'] = 'string';
    $expected['mapping']['uuid']['label'] = 'UUID';
    $expected['mapping']['langcode']['type'] = 'string';
    $expected['mapping']['langcode']['label'] = 'Default language';
    $expected['mapping']['status']['type'] = 'boolean';
    $expected['mapping']['status']['label'] = 'Status';
    $expected['mapping']['dependencies']['type'] = 'config_dependencies';
    $expected['mapping']['dependencies']['label'] = 'Dependencies';
    $expected['mapping']['name']['type'] = 'string';
    $expected['mapping']['label']['type'] = 'label';
    $expected['mapping']['label']['label'] = 'Label';
    $expected['mapping']['effects']['type'] = 'sequence';
    $expected['mapping']['effects']['sequence'][0]['type'] = 'mapping';
    $expected['mapping']['effects']['sequence'][0]['mapping']['id']['type'] = 'string';
    $expected['mapping']['effects']['sequence'][0]['mapping']['data']['type'] = 'image.effect.[%parent.id]';
    $expected['mapping']['effects']['sequence'][0]['mapping']['weight']['type'] = 'integer';
    $expected['mapping']['effects']['sequence'][0]['mapping']['uuid']['type'] = 'string';
    $expected['mapping']['third_party_settings']['type'] = 'sequence';
    $expected['mapping']['third_party_settings']['label'] = 'Third party settings';
    $expected['mapping']['third_party_settings']['sequence'][0]['type'] = 'image_style.third_party.[%key]';
    $expected['type'] = 'image.style.*';

    $this->assertEqual($definition, $expected);

    // More complex, type based on a complex one.
    $definition = \Drupal::service('config.typed')->getDefinition('image.effect.image_scale');
    // This should be the schema for image.effect.image_scale.
    $expected = array();
    $expected['label'] = 'Image scale';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['mapping']['width']['type'] = 'integer';
    $expected['mapping']['width']['label'] = 'Width';
    $expected['mapping']['height']['type'] = 'integer';
    $expected['mapping']['height']['label'] = 'Height';
    $expected['mapping']['upscale']['type'] = 'boolean';
    $expected['mapping']['upscale']['label'] = 'Upscale';
    $expected['type'] = 'image.effect.image_scale';


    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for image.effect.image_scale');

    // Most complex case, get metadata for actual configuration element.
    $effects = \Drupal::service('config.typed')->get('image.style.medium')->get('effects');
    $definition = $effects['bddf0d06-42f9-4c75-a700-a33cafa25ea0']['data']->getDataDefinition()->toArray();
    // This should be the schema for image.effect.image_scale, reuse previous one.
    $expected['type'] =  'image.effect.image_scale';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for the first effect of image.style.medium');

    // More complex, several level deep test.
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.someschema.somemodule.section_one.subsection');
    // This should be the schema of config_schema_test.someschema.somemodule.*.*.
    $expected = array();
    $expected['label'] = 'Schema multiple filesytem marker test';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['testid']['type'] = 'string';
    $expected['mapping']['testid']['label'] = 'ID';
    $expected['mapping']['testdescription']['type'] = 'text';
    $expected['mapping']['testdescription']['label'] = 'Description';
    $expected['type'] = 'config_schema_test.someschema.somemodule.*.*';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for config_schema_test.someschema.somemodule.section_one.subsection');

    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.someschema.somemodule.section_two.subsection');
    // The other file should have the same schema.
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for config_schema_test.someschema.somemodule.section_two.subsection');
  }

  /**
   * Tests metadata retrieval with several levels of %parent indirection.
   */
  function testSchemaMappingWithParents() {
    $config_data = \Drupal::service('config.typed')->get('config_schema_test.someschema.with_parents');

    // Test fetching parent one level up.
    $entry = $config_data->get('one_level');
    $definition = $entry['testitem']->getDataDefinition()->toArray();
    $expected = array(
      'type' => 'config_schema_test.someschema.with_parents.key_1',
      'label' => 'Test item nested one level',
      'class' => '\Drupal\Core\TypedData\Plugin\DataType\String',
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
    );
    $this->assertEqual($definition, $expected);

    // Test fetching parent two levels up.
    $entry = $config_data->get('two_levels');
    $definition = $entry['wrapper']['testitem']->getDataDefinition()->toArray();
    $expected = array(
      'type' => 'config_schema_test.someschema.with_parents.key_2',
      'label' => 'Test item nested two levels',
      'class' => '\Drupal\Core\TypedData\Plugin\DataType\String',
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
    );
    $this->assertEqual($definition, $expected);

    // Test fetching parent three levels up.
    $entry = $config_data->get('three_levels');
    $definition = $entry['wrapper_1']['wrapper_2']['testitem']->getDataDefinition()->toArray();
    $expected = array(
      'type' => 'config_schema_test.someschema.with_parents.key_3',
      'label' => 'Test item nested three levels',
      'class' => '\Drupal\Core\TypedData\Plugin\DataType\String',
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
    );
    $this->assertEqual($definition, $expected);
  }

  /**
   * Tests metadata applied to configuration objects.
   */
  function testSchemaData() {
    // Try some simple properties.
    $meta = \Drupal::service('config.typed')->get('system.site');
    $property = $meta->get('name');
    $this->assertTrue($property instanceof StringInterface, 'Got the right wrapper fo the site name property.');
    $this->assertEqual($property->getValue(), 'Drupal', 'Got the right string value for site name data.');
    $definition = $property->getDataDefinition();
    $this->assertTrue($definition['translatable'], 'Got the right translatability setting for site name data.');

    $property = $meta->get('page')->get('front');
    $this->assertTrue($property instanceof StringInterface, 'Got the right wrapper fo the page.front property.');
    $this->assertEqual($property->getValue(), 'user', 'Got the right value for page.front data.');
    $definition = $property->getDataDefinition();
    $this->assertTrue(empty($definition['translatable']), 'Got the right translatability setting for page.front data.');

    // Check nested array of properties.
    $list = $meta->get('page');
    $this->assertEqual(count($list), 3, 'Got a list with the right number of properties for site page data');
    $this->assertTrue(isset($list['front']) && isset($list['403']) && isset($list['404']), 'Got a list with the right properties for site page data.');
    $this->assertEqual($list['front']->getValue(), 'user', 'Got the right value for page.front data from the list.');

    // And test some ComplexDataInterface methods.
    $properties = $list->getProperties();
    $this->assertTrue(count($properties) == 3 && $properties['front'] == $list['front'], 'Got the right properties for site page.');
    $values = $list->toArray();
    $this->assertTrue(count($values) == 3 && $values['front'] == 'user', 'Got the right property values for site page.');

    // Now let's try something more complex, with nested objects.
    $wrapper = \Drupal::service('config.typed')->get('image.style.large');
    $effects = $wrapper->get('effects');

    // The function is_array() doesn't work with ArrayAccess, so we use count().
    $this->assertTrue(count($effects) == 1, 'Got an array with effects for image.style.large data');
    $uuid = key($effects->getValue());
    $effect = $effects[$uuid];
    $this->assertTrue(count($effect['data']) && $effect['id']->getValue() == 'image_scale', 'Got data for the image scale effect from metadata.');
    $this->assertTrue($effect['data']['width'] instanceof IntegerInterface, 'Got the right type for the scale effect width.');
    $this->assertEqual($effect['data']['width']->getValue(), 480, 'Got the right value for the scale effect width.' );

    // Finally update some object using a configuration wrapper.
    $new_slogan = 'Site slogan for testing configuration metadata';
    $wrapper = \Drupal::service('config.typed')->get('system.site');
    $wrapper->set('slogan', $new_slogan);
    $site_slogan = $wrapper->get('slogan');
    $this->assertEqual($site_slogan->getValue(), $new_slogan, 'Successfully updated the contained configuration data');
  }

  /**
   * Test configuration value data type enforcement using schemas.
   */
  public function testConfigSaveWithSchema() {
    $untyped_values = array(
      'string' => 1,
      'empty_string' => '',
      'null_string' => NULL,
      'integer' => '100',
      'null_integer' => '',
      'boolean' => 1,
      // If the config schema doesn't have a type it shouldn't be casted.
      'no_type' => 1,
      'mapping' => array(
        'string' => 1
      ),
      'float' => '3.14',
      'null_float' => '',
      'sequence' => array (1, 0, 1),
      // Not in schema and therefore should be left untouched.
      'not_present_in_schema' => TRUE,
      // Test a custom type.
      'config_schema_test_integer' => '1',
      'config_schema_test_integer_empty_string' => '',
    );
    $untyped_to_typed = $untyped_values;

    $typed_values = array(
      'string' => '1',
      'empty_string' => '',
      'null_string' => NULL,
      'integer' => 100,
      'null_integer' => NULL,
      'boolean' => TRUE,
      'no_type' => 1,
      'mapping' => array(
        'string' => '1'
      ),
      'float' => 3.14,
      'null_float' => NULL,
      'sequence' => array (TRUE, FALSE, TRUE),
      'not_present_in_schema' => TRUE,
      'config_schema_test_integer' => 1,
      'config_schema_test_integer_empty_string' => NULL,
    );

    // Save config which has a schema that enforces types.
    \Drupal::config('config_schema_test.schema_data_types')
      ->setData($untyped_to_typed)
      ->save();
    $this->assertIdentical(\Drupal::config('config_schema_test.schema_data_types')->get(), $typed_values);

    // Save config which does not have a schema that enforces types.
    \Drupal::config('config_schema_test.no_schema_data_types')
      ->setData($untyped_values)
      ->save();
    $this->assertIdentical(\Drupal::config('config_schema_test.no_schema_data_types')->get(), $untyped_values);

    // Ensure that configuration objects with keys marked as ignored are not
    // changed when saved. The 'config_schema_test.ignore' will have been saved
    // during the installation of configuration in the setUp method.
    $extension_path = drupal_get_path('module', 'config_schema_test');
    $install_storage = new FileStorage($extension_path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $original_data = $install_storage->read('config_schema_test.ignore');
    $this->assertIdentical(\Drupal::config('config_schema_test.ignore')->get(), $original_data);
  }

  /**
   * Tests fallback to a greedy wildcard.
   */
  function testSchemaFallback() {
    $definition = \Drupal::service('config.typed')->getDefinition('config_schema_test.wildcard_fallback.something');
    // This should be the schema of config_schema_test.wildcard_fallback.*.
    $expected = array();
    $expected['label'] = 'Schema wildcard fallback test';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['definition_class'] = '\Drupal\Core\TypedData\MapDataDefinition';
    $expected['mapping']['testid']['type'] = 'string';
    $expected['mapping']['testid']['label'] = 'ID';
    $expected['mapping']['testdescription']['type'] = 'text';
    $expected['mapping']['testdescription']['label'] = 'Description';
    $expected['type'] = 'config_schema_test.wildcard_fallback.*';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for config_schema_test.wildcard_fallback.something');

    $definition2 = \Drupal::service('config.typed')->getDefinition('config_schema_test.wildcard_fallback.something.something');
    // This should be the schema of config_schema_test.wildcard_fallback.* as
    //well.
    $this->assertIdentical($definition, $definition2);
  }

  /**
   * Tests use of colons in schema type determination.
   *
   * @see \Drupal\Core\Config\TypedConfigManager::getFallbackName()
   */
  function testColonsInSchemaTypeDetermination() {
    $tests = \Drupal::service('config.typed')->get('config_schema_test.plugin_types')->get('tests');
    $definition = $tests[0]->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test.plugin_types.boolean');

    $definition = $tests[1]->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test.plugin_types.boolean:*');

    $definition = $tests[2]->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test.plugin_types.*');

    $definition = $tests[3]->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test.plugin_types.*');

    $tests = \Drupal::service('config.typed')->get('config_schema_test.plugin_types')->get('test_with_parents');
    $definition = $tests[0]['settings']->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test_with_parents.plugin_types.boolean');

    $definition = $tests[1]['settings']->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test_with_parents.plugin_types.boolean:*');

    $definition = $tests[2]['settings']->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test_with_parents.plugin_types.*');

    $definition = $tests[3]['settings']->getDataDefinition()->toArray();
    $this->assertEqual($definition['type'], 'test_with_parents.plugin_types.*');
  }

}
