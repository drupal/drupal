<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigCRUDTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\ConfigNameException;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests CRUD operations on configuration objects.
 */
class ConfigCRUDTest extends DrupalUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'CRUD operations',
      'description' => 'Tests CRUD operations on configuration objects.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    $storage = $this->container->get('config.storage');
    $name = 'config_test.crud';

    $config = config($name);
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

    // Verify a call to config() immediately returns the updated value.
    $new_config = config($name);
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

    // Verify config() returns no data.
    $new_config = config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), TRUE);

    // Re-create the configuration object.
    $config->set('value', 're-created');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active configuration contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 're-created'));

    // Verify a call to config() immediately returns the updated value.
    $new_config = config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), FALSE);

    // Rename the configuration object.
    $new_name = 'config_test.crud_rename';
    $this->container->get('config.factory')->rename($name, $new_name);
    $renamed_config = config($new_name);
    $this->assertIdentical($renamed_config->get(), $config->get());
    $this->assertIdentical($renamed_config->isNew(), FALSE);

    // Ensure that the old configuration object is removed from both the cache
    // and the configuration storage.
    $config = config($name);
    $this->assertIdentical($config->get(), array());
    $this->assertIdentical($config->isNew(), TRUE);

    // Test renaming when config.factory does not have the object in its static
    // cache.
    $name = 'config_test.crud_rename';
    $config = config($name);
    $new_name = 'config_test.crud_rename_no_cache';
    $this->container->get('config.factory')->clearStaticCache()->rename($name, $new_name);
    $renamed_config = config($new_name);
    $this->assertIdentical($renamed_config->get(), $config->get());
    $this->assertIdentical($renamed_config->isNew(), FALSE);

    // Merge data into the configuration object.
    $new_config = config($new_name);
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
      config($name)->save();
      $this->fail($message);
    }
    catch (ConfigNameException $e) {
      $this->pass($message);
    }

    // Verify that a name longer than the maximum length causes an exception.
    $name = 'config_test.herman_melville.moby_dick_or_the_whale.harper_1851.now_small_fowls_flew_screaming_over_the_yet_yawning_gulf_a_sullen_white_surf_beat_against_its_steep_sides_then_all_collapsed_and_the_great_shroud_of_the_sea_rolled_on_as_it_rolled_five_thousand_years_ago';
    $message = 'Expected ConfigNameException was thrown for a name longer than Config::MAX_NAME_LENGTH.';
    try {
      config($name)->save();
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
        $config = config($name);
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
      $config = config($name);
      $config->save();
      $this->pass($message);
    }
    catch (\Exception $e) {
      $this->fail($message);
    }

    // Verify an exception is thrown when importing configuration with an
    // invalid name (missing a namespace).
    $message = 'Expected ConfigNameException was thrown when attempting to install invalid configuration.';
    try {
      $this->enableModules(array('config_test_invalid_name'));
      $this->installConfig(array('config_test_invalid_name'));
      $this->fail($message);
    }
    catch (ConfigNameException $e) {
      $this->pass($message);
    }

    // Write configuration with an invalid name (missing a namespace) to
    // staging.
    $staging = $this->container->get('config.storage.staging');
    $manifest_data = config('manifest.invalid_object_name')->get();
    $manifest_data['new']['name'] = 'invalid';
    $staging->write('manifest.invalid_object_name', $manifest_data);

    // Assert that config_import returns false indicating a failure.
    $this->assertFalse(config_import(), "Config import failed when trying to importing an object with an invalid name");
  }

}
