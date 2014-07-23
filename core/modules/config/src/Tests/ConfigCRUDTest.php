<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigCRUDTest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Config\InstallStorage;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\UnsupportedDataTypeConfigException;

/**
 * Tests CRUD operations on configuration objects.
 *
 * @group config
 */
class ConfigCRUDTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    $storage = $this->container->get('config.storage');
    $name = 'config_test.crud';

    $config = \Drupal::config($name);
    $this->assertIdentical($config->isNew(), TRUE);

    // Create a new configuration object.
    $config->set('value', 'initial');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active configuration contains the saved value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 'initial'));

    // Update the configuration object instance.
    $config->set('value', 'instance-update');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active configuration contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 'instance-update'));

    // Verify a call to \Drupal::config() immediately returns the updated value.
    $new_config = \Drupal::config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), FALSE);

    // Delete the configuration object.
    $config->delete();

    // Verify the configuration object is empty.
    $this->assertIdentical($config->get(), array());
    $this->assertIdentical($config->isNew(), TRUE);

    // Verify the active configuration contains no value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, FALSE);

    // Verify \Drupal::config() returns no data.
    $new_config = \Drupal::config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), TRUE);

    // Re-create the configuration object.
    $config->set('value', 're-created');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active configuration contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 're-created'));

    // Verify a call to \Drupal::config() immediately returns the updated value.
    $new_config = \Drupal::config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), FALSE);

    // Rename the configuration object.
    $new_name = 'config_test.crud_rename';
    $this->container->get('config.factory')->rename($name, $new_name);
    $renamed_config = \Drupal::config($new_name);
    $this->assertIdentical($renamed_config->get(), $config->get());
    $this->assertIdentical($renamed_config->isNew(), FALSE);

    // Ensure that the old configuration object is removed from both the cache
    // and the configuration storage.
    $config = \Drupal::config($name);
    $this->assertIdentical($config->get(), array());
    $this->assertIdentical($config->isNew(), TRUE);

    // Test renaming when config.factory does not have the object in its static
    // cache.
    $name = 'config_test.crud_rename';
    $config = \Drupal::config($name);
    $new_name = 'config_test.crud_rename_no_cache';
    $this->container->get('config.factory')->clearStaticCache()->rename($name, $new_name);
    $renamed_config = \Drupal::config($new_name);
    $this->assertIdentical($renamed_config->get(), $config->get());
    $this->assertIdentical($renamed_config->isNew(), FALSE);

    // Merge data into the configuration object.
    $new_config = \Drupal::config($new_name);
    $expected_values = array(
      'value' => 'herp',
      '404' => 'derp',
    );
    $new_config->merge($expected_values);
    $new_config->save();
    $this->assertIdentical($new_config->get('value'), $expected_values['value']);
    $this->assertIdentical($new_config->get('404'), $expected_values['404']);
  }

  /**
   * Tests the validation of configuration object names.
   */
  function testNameValidation() {
    // Verify that an object name without namespace causes an exception.
    $name = 'nonamespace';
    $message = 'Expected ConfigNameException was thrown for a name without a namespace.';
    try {
      \Drupal::config($name)->save();
      $this->fail($message);
    }
    catch (ConfigNameException $e) {
      $this->pass($message);
    }

    // Verify that a name longer than the maximum length causes an exception.
    $name = 'config_test.herman_melville.moby_dick_or_the_whale.harper_1851.now_small_fowls_flew_screaming_over_the_yet_yawning_gulf_a_sullen_white_surf_beat_against_its_steep_sides_then_all_collapsed_and_the_great_shroud_of_the_sea_rolled_on_as_it_rolled_five_thousand_years_ago';
    $message = 'Expected ConfigNameException was thrown for a name longer than Config::MAX_NAME_LENGTH.';
    try {
      \Drupal::config($name)->save();
      $this->fail($message);
    }
    catch (ConfigNameException $e) {
      $this->pass($message);
    }

    // Verify that disallowed characters in the name cause an exception.
    $characters = $test_characters = array(':', '?', '*', '<', '>', '"', '\'', '/', '\\');
    foreach ($test_characters as $i => $c) {
      try {
        $name = 'namespace.object' . $c;
        $config = \Drupal::config($name);
        $config->save();
      }
      catch (ConfigNameException $e) {
        unset($test_characters[$i]);
      }
    }
    $this->assertTrue(empty($test_characters), format_string('Expected ConfigNameException was thrown for all invalid name characters: @characters', array(
      '@characters' => implode(' ', $characters),
    )));

    // Verify that a valid config object name can be saved.
    $name = 'namespace.object';
    $message = 'ConfigNameException was not thrown for a valid object name.';
    try {
      $config = \Drupal::config($name);
      $config->save();
      $this->pass($message);
    }
    catch (\Exception $e) {
      $this->fail($message);
    }

  }

  /**
   * Tests the validation of configuration object values.
   */
  function testValueValidation() {
    // Verify that setData() will catch dotted keys.
    $message = 'Expected ConfigValueException was thrown from setData() for value with dotted keys.';
    try {
      \Drupal::config('namespace.object')->setData(array('key.value' => 12))->save();
      $this->fail($message);
    }
    catch (ConfigValueException $e) {
      $this->pass($message);
    }

    // Verify that set() will catch dotted keys.
    $message = 'Expected ConfigValueException was thrown from set() for value with dotted keys.';
    try {
      \Drupal::config('namespace.object')->set('foo', array('key.value' => 12))->save();
      $this->fail($message);
    }
    catch (ConfigValueException $e) {
      $this->pass($message);
    }
  }

  /**
   * Tests data type handling.
   */
  public function testDataTypes() {
    \Drupal::moduleHandler()->install(array('config_test'));
    $storage = new DatabaseStorage($this->container->get('database'), 'config');
    $name = 'config_test.types';
    $config = $this->container->get('config.factory')->get($name);
    $original_content = file_get_contents(drupal_get_path('module', 'config_test') . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.yml");
    $this->verbose('<pre>' . $original_content . "\n" . var_export($storage->read($name), TRUE));

    // Verify variable data types are intact.
    $data = array(
      'array' => array(),
      'boolean' => TRUE,
      'exp' => 1.2e+34,
      'float' => 3.14159,
      'hex' => 0xC,
      'int' => 99,
      'octal' => 0775,
      'string' => 'string',
      'string_int' => '1',
    );
    $this->assertIdentical($config->get(), $data);

    // Re-set each key using Config::set().
    foreach($data as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    $this->assertIdentical($config->get(), $data);
    // Assert the data against the file storage.
    $this->assertIdentical($storage->read($name), $data);
    $this->verbose('<pre>' . $name . var_export($storage->read($name), TRUE));

    // Set data using config::setData().
    $config->setData($data)->save();
    $this->assertIdentical($config->get(), $data);
    $this->assertIdentical($storage->read($name), $data);

    // Test that setting an unsupported type for a config object with a schema
    // fails.
    try {
      $config->set('stream', fopen(__FILE__, 'r'))->save();
      $this->fail('No Exception thrown upon saving invalid data type.');
    }
    catch (UnsupportedDataTypeConfigException $e) {
      $this->pass(String::format('%class thrown upon saving invalid data type.', array(
        '%class' => get_class($e),
      )));
    }

    // Test that setting an unsupported type for a config object with no schema
    // also fails.
    $typed_config_manager = $this->container->get('config.typed');
    $config_name = 'config_test.no_schema';
    $config = $this->container->get('config.factory')->get($config_name);
    $this->assertFalse($typed_config_manager->hasConfigSchema($config_name));

    try {
      $config->set('stream', fopen(__FILE__, 'r'))->save();
      $this->fail('No Exception thrown upon saving invalid data type.');
    }
    catch (UnsupportedDataTypeConfigException $e) {
      $this->pass(String::format('%class thrown upon saving invalid data type.', array(
        '%class' => get_class($e),
      )));
    }
  }

}
