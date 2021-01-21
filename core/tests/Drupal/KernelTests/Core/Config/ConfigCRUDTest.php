<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\UnsupportedDataTypeConfigException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests CRUD operations on configuration objects.
 *
 * @group config
 */
class ConfigCRUDTest extends KernelTestBase {

  /**
   * Exempt from strict schema checking.
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  /**
   * Tests CRUD operations.
   */
  public function testCRUD() {
    $event_dispatcher = $this->container->get('event_dispatcher');
    $typed_config_manager = $this->container->get('config.typed');

    $storage = $this->container->get('config.storage');
    $collection_storage = $storage->createCollection('test_collection');

    $config_factory = $this->container->get('config.factory');
    $name = 'config_test.crud';

    // Create a new configuration object in the default collection.
    $config = $this->config($name);
    $this->assertTrue($config->isNew());

    $config->set('value', 'initial');
    $config->save();
    $this->assertFalse($config->isNew());

    // Verify the active configuration contains the saved value.
    $actual_data = $storage->read($name);
    $this->assertSame(['value' => 'initial'], $actual_data);

    // Verify the config factory contains the saved value.
    $actual_data = $config_factory->get($name)->getRawData();
    $this->assertSame(['value' => 'initial'], $actual_data);

    // Create another instance of the config object using a custom collection.
    $collection_config = new Config(
      $name,
      $collection_storage,
      $event_dispatcher,
      $typed_config_manager
    );
    $collection_config->set('value', 'overridden');
    $collection_config->save();

    // Verify that the config factory still returns the right value, from the
    // config instance in the default collection.
    $actual_data = $config_factory->get($name)->getRawData();
    $this->assertSame(['value' => 'initial'], $actual_data);

    // Update the configuration object instance.
    $config->set('value', 'instance-update');
    $config->save();
    $this->assertFalse($config->isNew());

    // Verify the active configuration contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertSame(['value' => 'instance-update'], $actual_data);

    // Verify a call to $this->config() immediately returns the updated value.
    $new_config = $this->config($name);
    $this->assertSame($config->get(), $new_config->get());
    $this->assertFalse($config->isNew());

    // Pollute the config factory static cache.
    $config_factory->getEditable($name);

    // Delete the config object that uses a custom collection. This should not
    // affect the instance returned by the config factory which depends on the
    // default collection storage.
    $collection_config->delete();
    $actual_config = $config_factory->get($name);
    $this->assertFalse($actual_config->isNew());
    $this->assertSame(['value' => 'instance-update'], $actual_config->getRawData());

    // Delete the configuration object.
    $config->delete();

    // Verify the configuration object is empty.
    $this->assertSame([], $config->get());
    $this->assertTrue($config->isNew());

    // Verify that all copies of the configuration has been removed from the
    // static cache.
    $this->assertTrue($config_factory->getEditable($name)->isNew());

    // Verify the active configuration contains no value.
    $actual_data = $storage->read($name);
    $this->assertFalse($actual_data);

    // Verify $this->config() returns no data.
    $new_config = $this->config($name);
    $this->assertSame($config->get(), $new_config->get());
    $this->assertTrue($config->isNew());

    // Re-create the configuration object.
    $config->set('value', 're-created');
    $config->save();
    $this->assertFalse($config->isNew());

    // Verify the active configuration contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertSame(['value' => 're-created'], $actual_data);

    // Verify a call to $this->config() immediately returns the updated value.
    $new_config = $this->config($name);
    $this->assertSame($config->get(), $new_config->get());
    $this->assertFalse($config->isNew());

    // Rename the configuration object.
    $new_name = 'config_test.crud_rename';
    $this->container->get('config.factory')->rename($name, $new_name);
    $renamed_config = $this->config($new_name);
    $this->assertSame($config->get(), $renamed_config->get());
    $this->assertFalse($renamed_config->isNew());

    // Ensure that the old configuration object is removed from both the cache
    // and the configuration storage.
    $config = $this->config($name);
    $this->assertSame([], $config->get());
    $this->assertTrue($config->isNew());

    // Test renaming when config.factory does not have the object in its static
    // cache.
    $name = 'config_test.crud_rename';
    // Pollute the non-overrides static cache.
    $config_factory->getEditable($name);
    // Pollute the overrides static cache.
    $config = $config_factory->get($name);
    // Rename and ensure that happened properly.
    $new_name = 'config_test.crud_rename_no_cache';
    $config_factory->rename($name, $new_name);
    $renamed_config = $config_factory->get($new_name);
    $this->assertSame($config->get(), $renamed_config->get());
    $this->assertFalse($renamed_config->isNew());
    // Ensure the overrides static cache has been cleared.
    $this->assertTrue($config_factory->get($name)->isNew());
    // Ensure the non-overrides static cache has been cleared.
    $this->assertTrue($config_factory->getEditable($name)->isNew());

    // Merge data into the configuration object.
    $new_config = $this->config($new_name);
    $expected_values = [
      'value' => 'herp',
      '404' => 'derp',
    ];
    $new_config->merge($expected_values);
    $new_config->save();
    $this->assertSame($expected_values['value'], $new_config->get('value'));
    $this->assertSame($expected_values['404'], $new_config->get('404'));

    // Test that getMultiple() does not return new config objects that were
    // previously accessed with get()
    $new_config = $config_factory->get('non_existing_key');
    $this->assertTrue($new_config->isNew());
    $this->assertCount(0, $config_factory->loadMultiple(['non_existing_key']), 'loadMultiple() does not return new objects');
  }

  /**
   * Tests the validation of configuration object names.
   */
  public function testNameValidation() {
    // Verify that an object name without namespace causes an exception.
    $name = 'nonamespace';
    try {
      $this->config($name)->save();
      $this->fail('Expected ConfigNameException was thrown for a name without a namespace.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ConfigNameException::class, $e);
    }

    // Verify that a name longer than the maximum length causes an exception.
    $name = 'config_test.herman_melville.moby_dick_or_the_whale.harper_1851.now_small_fowls_flew_screaming_over_the_yet_yawning_gulf_a_sullen_white_surf_beat_against_its_steep_sides_then_all_collapsed_and_the_great_shroud_of_the_sea_rolled_on_as_it_rolled_five_thousand_years_ago';
    try {
      $this->config($name)->save();
      $this->fail('Expected ConfigNameException was thrown for a name longer than Config::MAX_NAME_LENGTH.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ConfigNameException::class, $e);
    }

    // Verify that disallowed characters in the name cause an exception.
    $characters = $test_characters = [':', '?', '*', '<', '>', '"', '\'', '/', '\\'];
    foreach ($test_characters as $i => $c) {
      try {
        $name = 'namespace.object' . $c;
        $config = $this->config($name);
        $config->save();
      }
      catch (ConfigNameException $e) {
        unset($test_characters[$i]);
      }
    }
    $this->assertTrue(empty($test_characters), new FormattableMarkup('Expected ConfigNameException was thrown for all invalid name characters: @characters', [
      '@characters' => implode(' ', $characters),
    ]));

    // Verify that a valid config object name can be saved.
    $name = 'namespace.object';
    try {
      $config = $this->config($name);
      $config->save();
    }
    catch (ConfigNameException $e) {
      $this->fail('ConfigNameException was not thrown for a valid object name.');
    }

  }

  /**
   * Tests the validation of configuration object values.
   */
  public function testValueValidation() {
    // Verify that setData() will catch dotted keys.
    try {
      $this->config('namespace.object')->setData(['key.value' => 12])->save();
      $this->fail('Expected ConfigValueException was thrown from setData() for value with dotted keys.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ConfigValueException::class, $e);
    }

    // Verify that set() will catch dotted keys.
    try {
      $this->config('namespace.object')->set('foo', ['key.value' => 12])->save();
      $this->fail('Expected ConfigValueException was thrown from set() for value with dotted keys.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ConfigValueException::class, $e);
    }
  }

  /**
   * Tests data type handling.
   */
  public function testDataTypes() {
    \Drupal::service('module_installer')->install(['config_test']);
    $storage = new DatabaseStorage($this->container->get('database'), 'config');
    $name = 'config_test.types';
    $config = $this->config($name);
    $original_content = file_get_contents(drupal_get_path('module', 'config_test') . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY . "/$name.yml");
    $this->verbose('<pre>' . $original_content . "\n" . var_export($storage->read($name), TRUE));

    // Verify variable data types are intact.
    $data = [
      'array' => [],
      'boolean' => TRUE,
      'exp' => 1.2e+34,
      'float' => 3.14159,
      'float_as_integer' => (float) 1,
      'hex' => 0xC,
      'int' => 99,
      'octal' => 0775,
      'string' => 'string',
      'string_int' => '1',
    ];
    $data['_core']['default_config_hash'] = Crypt::hashBase64(serialize($data));
    $this->assertSame($data, $config->get());

    // Re-set each key using Config::set().
    foreach ($data as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    $this->assertSame($data, $config->get());
    // Assert the data against the file storage.
    $this->assertSame($data, $storage->read($name));
    $this->verbose('<pre>' . $name . var_export($storage->read($name), TRUE));

    // Set data using config::setData().
    $config->setData($data)->save();
    $this->assertSame($data, $config->get());
    $this->assertSame($data, $storage->read($name));

    // Test that schema type enforcement can be overridden by trusting the data.
    $this->assertSame(99, $config->get('int'));
    $config->set('int', '99')->save(TRUE);
    $this->assertSame('99', $config->get('int'));
    // Test that re-saving without testing the data enforces the schema type.
    $config->save();
    $this->assertSame($data, $config->get());

    // Test that setting an unsupported type for a config object with a schema
    // fails.
    try {
      $config->set('stream', fopen(__FILE__, 'r'))->save();
      $this->fail('No Exception thrown upon saving invalid data type.');
    }
    catch (UnsupportedDataTypeConfigException $e) {
      // Expected exception; just continue testing.
    }

    // Test that setting an unsupported type for a config object with no schema
    // also fails.
    $typed_config_manager = $this->container->get('config.typed');
    $config_name = 'config_test.no_schema';
    $config = $this->config($config_name);
    $this->assertFalse($typed_config_manager->hasConfigSchema($config_name));

    try {
      $config->set('stream', fopen(__FILE__, 'r'))->save();
      $this->fail('No Exception thrown upon saving invalid data type.');
    }
    catch (UnsupportedDataTypeConfigException $e) {
      // Expected exception; just continue testing.
    }
  }

}
