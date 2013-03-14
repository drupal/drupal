<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigSchemaTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\TypedConfig;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests schema for configuration objects.
 */
class ConfigSchemaTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'locale', 'image', 'config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration schema',
      'description' => 'Tests Metadata for configuration objects.',
      'group' => 'Configuration',
    );
  }

  public function setUp() {
    parent::setUp();
    config_install_default_config('module', 'system');
    config_install_default_config('module', 'image');
    config_install_default_config('module', 'config_test');
  }

  /**
   * Tests the basic metadata retrieval layer.
   */
  function testSchemaMapping() {
    // Nonexistent configuration key will have Unknown as metadata.
    $definition = config_typed()->getDefinition('config_test.no_such_key');
    $expected = array();
    $expected['label'] = 'Unknown';
    $expected['class'] = '\Drupal\Core\Config\Schema\Property';
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for nonexistent configuration.');

    // Configuration file without schema will return Unknown as well.
    $definition = config_typed()->getDefinition('config_test.noschema');
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for configuration with no schema.');

    // Configuration file with only some schema.
    $definition = config_typed()->getDefinition('config_test.someschema');
    $expected = array();
    $expected['label'] = 'Schema test data';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['testitem'] = array('label' => 'Test item');
    $expected['mapping']['testlist'] = array('label' => 'Test list');
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for configuration with only some schema.');

    // Check type detection on elements with undefined types.
    $config = config_typed()->get('config_test.someschema');
    $definition = $config['testitem']->getDefinition();
    $expected = array();
    $expected['label'] = 'Test item';
    $expected['class'] = '\Drupal\Core\TypedData\Type\String';
    $expected['type'] = 'string';
    $this->assertEqual($definition, $expected, 'Automatic type detection on string item worked.');
    $definition = $config['testlist']->getDefinition();
    $expected = array();
    $expected['label'] = 'Test list';
    $expected['class'] = '\Drupal\Core\Config\Schema\Property';
    $expected['type'] = 'undefined';
    $this->assertEqual($definition, $expected, 'Automatic type fallback on non-string item worked.');

    // Simple case, straight metadata.
    $definition = config_typed()->getDefinition('system.maintenance');
    $expected = array();
    $expected['label'] = 'Maintenance mode';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['enabled'] = array(
      'label' => 'Put site into maintenance mode',
      'type' => 'boolean'
    );
    $expected['mapping']['message'] = array(
      'label' =>  'Message to display when in maintenance mode',
      'type' => 'text',
    );
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for system.maintenance');

    // More complex case, generic type. Metadata for image style.
    $definition = config_typed()->getDefinition('image.style.large');
    $expected = array();
    $expected['label'] = 'Image style';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['name']['type'] = 'string';
    $expected['mapping']['label']['type'] = 'label';
    $expected['mapping']['effects']['type'] = 'sequence';
    $expected['mapping']['effects']['sequence'][0]['type'] = 'mapping';
    $expected['mapping']['effects']['sequence'][0]['mapping']['name']['type'] = 'string';
    $expected['mapping']['effects']['sequence'][0]['mapping']['data']['type'] = 'image.effect.[%parent.name]';
    $expected['mapping']['effects']['sequence'][0]['mapping']['weight']['type'] = 'integer';
    $expected['mapping']['effects']['sequence'][0]['mapping']['ieid']['type'] = 'string';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for image.style.large');

    // More complex, type based on a complex one.
    $definition = config_typed()->getDefinition('image.effect.image_scale');
    // This should be the schema for image.effect.image_scale.
    $expected = array();
    $expected['label'] = 'Image scale';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['width']['type'] = 'integer';
    $expected['mapping']['width']['label'] = 'Width';
    $expected['mapping']['height']['type'] = 'integer';
    $expected['mapping']['height']['label'] = 'Height';
    $expected['mapping']['upscale']['type'] = 'boolean';
    $expected['mapping']['upscale']['label'] = 'Upscale';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for image.effect.image_scale');

    // Most complex case, get metadata for actual configuration element.
    $effects = config_typed()->get('image.style.medium')->get('effects');
    $definition = $effects['bddf0d06-42f9-4c75-a700-a33cafa25ea0']['data']->getDefinition();
    // This should be the schema for image.effect.image_scale, reuse previous one.
    $expected['type'] =  'image.effect.image_scale';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for the first effect of image.style.medium');

    // More complex, multiple filesystem marker test.
    $definition = config_typed()->getDefinition('config_test.someschema.somemodule.section_one.subsection');
    // This should be the schema of config_test.someschema.somemodule.*.*.
    $expected = array();
    $expected['label'] = 'Schema multiple filesytem marker test';
    $expected['class'] = '\Drupal\Core\Config\Schema\Mapping';
    $expected['mapping']['id']['type'] = 'string';
    $expected['mapping']['id']['label'] = 'ID';
    $expected['mapping']['description']['type'] = 'text';
    $expected['mapping']['description']['label'] = 'Description';

    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for config_test.someschema.somemodule.section_one.subsection');

    $definition = config_typed()->getDefinition('config_test.someschema.somemodule.section_two.subsection');
    // The other file should have the same schema.
    $this->assertEqual($definition, $expected, 'Retrieved the right metadata for config_test.someschema.somemodule.section_two.subsection');

  }

  /**
   * Tests metadata applied to configuration objects.
   */
  function testSchemaData() {
    // Try some simple properties.
    $meta = config_typed()->get('system.site');
    $property = $meta->get('name');
    $this->assertTrue(is_a($property, 'Drupal\Core\TypedData\Type\String'), 'Got the right wrapper fo the site name property.');
    $this->assertEqual($property->getType(), 'label', 'Got the right string type for site name data.');
    $this->assertEqual($property->getValue(), 'Drupal', 'Got the right string value for site name data.');

    $property = $meta->get('page')->get('front');
    $this->assertTrue(is_a($property, 'Drupal\Core\TypedData\Type\String'), 'Got the right wrapper fo the page.front property.');
    $this->assertEqual($property->getType(), 'path', 'Got the right type for page.front data (undefined).');
    $this->assertEqual($property->getValue(), 'user', 'Got the right value for page.front data.');

    // Check nested array of properties.
    $list = $meta->get('page');
    $this->assertEqual(count($list), 3, 'Got a list with the right number of properties for site page data');
    $this->assertTrue(isset($list['front']) && isset($list['403']) && isset($list['404']), 'Got a list with the right properties for site page data.');
    $this->assertEqual($list['front']->getValue(), 'user', 'Got the right value for page.front data from the list.');

    // And test some ComplexDataInterface methods.
    $properties = $list->getProperties();
    $this->assertTrue(count($properties) == 3 && $properties['front'] == $list['front'], 'Got the right properties for site page.');
    $values = $list->getPropertyValues();
    $this->assertTrue(count($values) == 3 && $values['front'] == 'user', 'Got the right property values for site page.');

    // Now let's try something more complex, with nested objects.
    $wrapper = config_typed()->get('image.style.large');
    $effects = $wrapper->get('effects');

    // The function is_array() doesn't work with ArrayAccess, so we use count().
    $this->assertTrue(count($effects) == 1, 'Got an array with effects for image.style.large data');
    $ieid = key($effects->getValue());
    $effect = $effects[$ieid];
    $this->assertTrue(count($effect['data']) && $effect['name']->getValue() == 'image_scale', 'Got data for the image scale effect from metadata.');
    $this->assertEqual($effect['data']['width']->getType(), 'integer', 'Got the right type for the scale effect width.');
    $this->assertEqual($effect['data']['width']->getValue(), 480, 'Got the right value for the scale effect width.' );

    // Finally update some object using a configuration wrapper.
    $new_slogan = 'Site slogan for testing configuration metadata';
    $wrapper = config_typed()->get('system.site');
    $wrapper->set('slogan', $new_slogan);
    $site_slogan = $wrapper->get('slogan');
    $this->assertEqual($site_slogan->getValue(), $new_slogan, 'Successfully updated the contained configuration data');
  }

}
