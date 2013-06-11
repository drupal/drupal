<?php

/**
 * @file
 * Contains \Drupal\Tests\UnitTestCase.
 */

namespace Drupal\Tests;

/**
 * Provides a base class and helpers for Drupal unit tests.
 */
abstract class UnitTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * This method exists to support the simpletest UI runner.
   *
   * It should eventually be replaced with something native to phpunit.
   *
   * Also, this method is empty because you can't have an abstract static
   * method. Sub-classes should always override it.
   *
   * @return array
   *   An array describing the test like so:
   *   array(
   *     'name' => 'Something Test',
   *     'description' => 'Tests Something',
   *     'group' => 'Something',
   *   )
   */
  public static function getInfo() {
    throw new \RuntimeException("Sub-class must implement the getInfo method!");
  }

  /**
   * Generates a random string containing letters and numbers.
   *
   * The string will always start with a letter. The letters may be upper or
   * lower case. This method is better for restricted inputs that do not accept
   * certain characters. For example, when testing input fields that require
   * machine readable values (i.e. without spaces and non-standard characters)
   * this method is best.
   *
   * Do not use this method when testing unvalidated user input. Instead, use
   * Drupal\simpletest\TestBase::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see Drupal\simpletest\TestBase::randomString()
   */
  public static function randomName($length = 8) {
    $values = array_merge(range(65, 90), range(97, 122), range(48, 57));
    $max = count($values) - 1;
    $str = chr(mt_rand(97, 122));
    for ($i = 1; $i < $length; $i++) {
      $str .= chr($values[mt_rand(0, $max)]);
    }
    return $str;
  }

  /**
   * Returns a stub config factory that behaves according to the passed in array.
   *
   * Use this to generate a config factory that will return the desired values
   * for the given config names.
   *
   * @param array $configs
   *   An associative array of configuration settings whose keys are configuration
   *   object names and whose values are key => value arrays for the configuration
   *   object in question.
   *
   * @return \PHPUnit_Framework_MockObject_MockBuilder
   *   A MockBuilder object for the ConfigFactory with the desired return values.
   */
  public function getConfigFactoryStub($configs) {
    $config_map = array();
    // Construct the desired configuration object stubs, each with its own
    // desired return map.
    foreach ($configs as $config_name => $config_values) {
      $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
        ->disableOriginalConstructor()
        ->getMock();
      $map = array();
      foreach ($config_values as $key => $value) {
        $map[] = array($key, $value);
      }
      // Also allow to pass in no argument.
      $map[] = array('', $config_values);

      $config_object->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));

      $config_map[] = array($config_name, $config_object);
    }
    // Construct a config factory with the array of configuration object stubs
    // as its return map.
    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $config_factory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($config_map));
    return $config_factory;
  }

  /**
   * Returns a stub config storage that returns the supplied configuration.
   *
   * @param array $configs
   *   An associative array of configuration settings whose keys are
   *   configuration object names and whose values are key => value arrays
   *   for the configuration object in question.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   A mocked config storage.
   */
  public function getConfigStorageStub(array $configs) {
    $config_storage = $this->getMock('Drupal\Core\Config\NullStorage');
    $config_storage->expects($this->any())
      ->method('listAll')
      ->will($this->returnValue(array_keys($configs)));

    foreach ($configs as $name => $config) {
      $config_storage->expects($this->any())
        ->method('read')
        ->with($this->equalTo($name))
        ->will($this->returnValue($config));
    }
    return $config_storage;
  }

}
