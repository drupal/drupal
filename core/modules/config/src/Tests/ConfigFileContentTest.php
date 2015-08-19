<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigFileContentTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\FileStorage;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests reading and writing of configuration files.
 *
 * @group config
 */
class ConfigFileContentTest extends KernelTestBase {

  /**
   * Exempt from strict schema checking.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests setting, writing, and reading of a configuration setting.
   */
  function testReadWriteConfig() {
    $storage = $this->container->get('config.storage');

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
      ),
    );
    $casting_array_key = 'casting_array';
    $casting_array_false_value_key = 'casting_array.cast.false';
    $casting_array_value = array(
      'cast' => array(
        'false' => FALSE,
      ),
    );
    $nested_array_key = 'nested.array';
    $true_key = 'true';
    $false_key = 'false';

    // Attempt to read non-existing configuration.
    $config = $this->config($name);

    // Verify a configuration object is returned.
    $this->assertEqual($config->getName(), $name);
    $this->assertTrue($config, 'Config object created.');

    // Verify the configuration object is empty.
    $this->assertEqual($config->get(), array(), 'New config object is empty.');

    // Verify nothing was saved.
    $data = $storage->read($name);
    $this->assertIdentical($data, FALSE);

    // Add a top level value.
    $config = $this->config($name);
    $config->set($key, $value);

    // Add a nested value.
    $config->set($nested_key, $nested_value);

    // Add an array.
    $config->set($array_key, $array_value);

    // Add a nested array.
    $config->set($nested_array_key, $array_value);

    // Add a boolean false value. Should get cast to 0.
    $config->set($false_key, FALSE);

    // Add a boolean true value. Should get cast to 1.
    $config->set($true_key, TRUE);

    // Add a null value. Should get cast to an empty string.
    $config->set('null', NULL);

    // Add an array with a nested boolean false that should get cast to 0.
    $config->set($casting_array_key, $casting_array_value);
    $config->save();

    // Verify the database entry exists.
    $data = $storage->read($name);
    $this->assertTrue($data);

    // Read top level value.
    $config = $this->config($name);
    $this->assertEqual($config->getName(), $name);
    $this->assertTrue($config, 'Config object created.');
    $this->assertEqual($config->get($key), 'bar', 'Top level configuration value found.');

    // Read nested value.
    $this->assertEqual($config->get($nested_key), $nested_value, 'Nested configuration value found.');

    // Read array.
    $this->assertEqual($config->get($array_key), $array_value, 'Top level array configuration value found.');

    // Read nested array.
    $this->assertEqual($config->get($nested_array_key), $array_value, 'Nested array configuration value found.');

    // Read a top level value that doesn't exist.
    $this->assertNull($config->get('i_dont_exist'), 'Non-existent top level value returned NULL.');

    // Read a nested value that doesn't exist.
    $this->assertNull($config->get('i.dont.exist'), 'Non-existent nested value returned NULL.');

    // Read false value.
    $this->assertEqual($config->get($false_key), '0', "Boolean FALSE value returned the string '0'.");

    // Read true value.
    $this->assertEqual($config->get($true_key), '1', "Boolean TRUE value returned the string '1'.");

    // Read null value.
    $this->assertIdentical($config->get('null'), NULL);

    // Read false that had been nested in an array value.
    $this->assertEqual($config->get($casting_array_false_value_key), '0', "Nested boolean FALSE value returned the string '0'.");

    // Unset a top level value.
    $config->clear($key);

    // Unset a nested value.
    $config->clear($nested_key);
    $config->save();
    $config = $this->config($name);

    // Read unset top level value.
    $this->assertNull($config->get($key), 'Top level value unset.');

    // Read unset nested value.
    $this->assertNull($config->get($nested_key), 'Nested value unset.');

    // Create two new configuration files to test listing.
    $config = $this->config('foo.baz');
    $config->set($key, $value);
    $config->save();

    // Test chained set()->save().
    $chained_name = 'biff.bang';
    $config = $this->config($chained_name);
    $config->set($key, $value)->save();

    // Verify the database entry exists from a chained save.
    $data = $storage->read($chained_name);
    $this->assertEqual($data, $config->get());

    // Get file listing for all files starting with 'foo'. Should return
    // two elements.
    $files = $storage->listAll('foo');
    $this->assertEqual(count($files), 2, 'Two files listed with the prefix \'foo\'.');

    // Get file listing for all files starting with 'biff'. Should return
    // one element.
    $files = $storage->listAll('biff');
    $this->assertEqual(count($files), 1, 'One file listed with the prefix \'biff\'.');

    // Get file listing for all files starting with 'foo.bar'. Should return
    // one element.
    $files = $storage->listAll('foo.bar');
    $this->assertEqual(count($files), 1, 'One file listed with the prefix \'foo.bar\'.');

    // Get file listing for all files starting with 'bar'. Should return
    // an empty array.
    $files = $storage->listAll('bar');
    $this->assertEqual($files, array(), 'No files listed with the prefix \'bar\'.');

    // Delete the configuration.
    $config = $this->config($name);
    $config->delete();

    // Verify the database entry no longer exists.
    $data = $storage->read($name);
    $this->assertIdentical($data, FALSE);
  }

  /**
   * Tests serialization of configuration to file.
   */
  function testSerialization() {
    $name = $this->randomMachineName(10) . '.' . $this->randomMachineName(10);
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

    // Encode and write, and reload and decode the configuration data.
    $filestorage = new FileStorage($this->configDirectories[CONFIG_ACTIVE_DIRECTORY]);
    $filestorage->write($name, $config_data);
    $config_parsed = $filestorage->read($name);

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
