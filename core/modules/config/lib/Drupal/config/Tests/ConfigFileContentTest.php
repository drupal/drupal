<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigFileContentTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\FileStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests reading and writing file contents.
 */
class ConfigFileContentTest extends WebTestBase {
  protected $fileExtension;

  public static function getInfo() {
    return array(
      'name' => 'File content',
      'description' => 'Tests reading and writing of configuration files.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    $this->fileExtension = FileStorage::getFileExtension();
  }

  /**
   * Tests setting, writing, and reading of a configuration setting.
   */
  function testReadWriteConfig() {
    $config_dir = config_get_config_directory();
    $name = 'foo.bar';
    $key = 'foo';
    $value = 'bar';
    $nested_key = 'biff.bang';
    $nested_value = 'pow';
    $array_key = 'array';
    $array_value = array(
      'foo' => 'bar',
      'biff' => array(
        'bang' => 'pow',
      )
    );
    $casting_array_key = 'casting_array';
    $casting_array_false_value_key = 'casting_array.cast.false';
    $casting_array_value = array(
      'cast' => array(
        'false' => FALSE,
      )
    );
    $nested_array_key = 'nested.array';
    $true_key = 'true';
    $false_key = 'false';

    // Attempt to read non-existing configuration.
    $config = config($name);

    // Verify an configuration object is returned.
//    $this->assertEqual($config->name, $name);
    $this->assertTrue($config, t('Config object created.'));

    // Verify the configuration object is empty.
    $this->assertEqual($config->get(), array(), t('New config object is empty.'));

    // Verify nothing was saved.
    $db_config = db_query('SELECT * FROM {config} WHERE name = :name', array(':name' => $name))->fetch();
    $this->assertIdentical($db_config, FALSE, t('Active store does not have a record for %name', array('%name' => $name)));
    $this->assertFalse(file_exists($config_dir . '/' . $name . '.' . $this->fileExtension), 'Configuration file does not exist.');

    // Add a top level value
    $config = config($name);
    $config->set($key, $value);

    // Add a nested value
    $config->set($nested_key, $nested_value);

    // Add an array
    $config->set($array_key, $array_value);

    // Add a nested array
    $config->set($nested_array_key, $array_value);

    // Add a boolean false value. Should get cast to 0
    $config->set($false_key, FALSE);

    // Add a boolean true value. Should get cast to 1
    $config->set($true_key, TRUE);

    // Add an array with a nested boolean false that should get cast to 0.
    $config->set($casting_array_key, $casting_array_value);
    $config->save();

    // Verify the database entry exists.
    $db_config = db_query('SELECT * FROM {config} WHERE name = :name', array(':name' => $name))->fetch();
    $this->assertEqual($db_config->name, $name, t('After saving configuration, active store has a record for %name', array('%name' => $name)));

    // Verify the file exists.
    $this->assertTrue(file_exists($config_dir . '/' . $name . '.' . $this->fileExtension), t('After saving configuration, config file exists.'));

    // Read top level value
    $config = config($name);
//    $this->assertEqual($config->name, $name);
    $this->assertTrue($config, 'Config object created.');
    $this->assertEqual($config->get($key), 'bar', t('Top level configuration value found.'));

    // Read nested value
    $this->assertEqual($config->get($nested_key), $nested_value, t('Nested configuration value found.'));

    // Read array
    $this->assertEqual($config->get($array_key), $array_value, t('Top level array configuration value found.'));

    // Read nested array
    $this->assertEqual($config->get($nested_array_key), $array_value, t('Nested array configuration value found.'));

    // Read a top level value that doesn't exist
    $this->assertNull($config->get('i_dont_exist'), t('Non-existent top level value returned NULL.'));

    // Read a nested value that doesn't exist
    $this->assertNull($config->get('i.dont.exist'), t('Non-existent nested value returned NULL.'));

    // Read false value
    $this->assertEqual($config->get($false_key), '0', t('Boolean FALSE value returned the string \'0\'.'));

    // Read true value
    $this->assertEqual($config->get($true_key), '1', t('Boolean TRUE value returned the string \'1\'.'));

    // Read false that had been nested in an array value
    $this->assertEqual($config->get($casting_array_false_value_key), '0', t('Nested boolean FALSE value returned the string \'0\'.'));

    // Unset a top level value
    $config->clear($key);

    // Unset a nested value
    $config->clear($nested_key);
    $config->save();
    $config = config($name);

    // Read unset top level value
    $this->assertNull($config->get($key), t('Top level value unset.'));

    // Read unset nested value
    $this->assertNull($config->get($nested_key), t('Nested value unset.'));

    // Create two new configuration files to test listing
    $config = config('foo.baz');
    $config->set($key, $value);
    $config->save();

    // Test chained set()->save()
    $chained_name = 'biff.bang';
    $config = config($chained_name);
    $config->set($key, $value)->save();

    // Verify the database entry exists from a chained save.
    $db_config = db_query('SELECT * FROM {config} WHERE name = :name', array(':name' => $chained_name))->fetch();
    $this->assertEqual($db_config->name, $chained_name, t('After saving configuration by chaining through set(), active store has a record for %name', array('%name' => $chained_name)));

    // Verify the file exists from a chained save.
    $this->assertTrue(file_exists($config_dir . '/' . $chained_name . '.' . $this->fileExtension), t('After saving configuration by chaining through set(), config file exists.'));

    // Get file listing for all files starting with 'foo'. Should return
    // two elements.
    $files = FileStorage::getNamesWithPrefix('foo');
    $this->assertEqual(count($files), 2, 'Two files listed with the prefix \'foo\'.');

    // Get file listing for all files starting with 'biff'. Should return
    // one element.
    $files = FileStorage::getNamesWithPrefix('biff');
    $this->assertEqual(count($files), 1, 'One file listed with the prefix \'biff\'.');

    // Get file listing for all files starting with 'foo.bar'. Should return
    // one element.
    $files = FileStorage::getNamesWithPrefix('foo.bar');
    $this->assertEqual(count($files), 1, 'One file listed with the prefix \'foo.bar\'.');

    // Get file listing for all files starting with 'bar'. Should return
    // an empty array.
    $files = FileStorage::getNamesWithPrefix('bar');
    $this->assertEqual($files, array(), 'No files listed with the prefix \'bar\'.');

    // Delete the configuration.
    $config = config($name);
    $config->delete();

    // Verify the database entry no longer exists.
    $db_config = db_query('SELECT * FROM {config} WHERE name = :name', array(':name' => $name))->fetch();
    $this->assertIdentical($db_config, FALSE);
    $this->assertFalse(file_exists($config_dir . '/' . $name . $this->fileExtension));

    // Attempt to delete non-existing configuration.
  }

  /**
   * Tests serialization of configuration to file.
   */
  function testConfigSerialization() {
    $name = $this->randomName(10) . '.' . $this->randomName(10);
    $config_data = array(
      // Indexed arrays; the order of elements is essential.
      'numeric keys' => array('i', 'n', 'd', 'e', 'x', 'e', 'd'),
      // Infinitely nested keys using arbitrary element names.
      'nested keys' => array(
        // HTML/XML in values.
        'HTML' => '<strong> <bold> <em> <blockquote>',
        // UTF-8 in values.
        'UTF-8' => 'FrançAIS is ÜBER-åwesome',
        // Unicode in keys and values.
        'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΣὨ' => 'αβγδεζηθικλμνξοσὠ',
      ),
      'invalid xml' => '</title><script type="text/javascript">alert("Title XSS!");</script> & < > " \' ',
    );

    // Attempt to read non-existing configuration.
    $config = config($name);

    foreach ($config_data as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();

    $config_filestorage = new FileStorage($name);
    $config_parsed = $config_filestorage->read();

    $key = 'numeric keys';
    $this->assertIdentical($config_data[$key], $config_parsed[$key]);

    $key = 'nested keys';
    $this->assertIdentical($config_data[$key], $config_parsed[$key]);

    $key = 'HTML';
    $this->assertIdentical($config_data['nested keys'][$key], $config_parsed['nested keys'][$key]);

    $key = 'UTF-8';
    $this->assertIdentical($config_data['nested keys'][$key], $config_parsed['nested keys'][$key]);

    $key = 'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΣὨ';
    $this->assertIdentical($config_data['nested keys'][$key], $config_parsed['nested keys'][$key]);

    $key = 'invalid xml';
    $this->assertIdentical($config_data[$key], $config_parsed[$key]);
  }
}
