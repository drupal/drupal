<?php

/**
 * @file
 * Contains \Drupal\views\Tests\PluginBaseUnitTest.
 */

namespace Drupal\views\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;

/**
 * Tests code of the views plugin base class.
 *
 * @see \Drupal\views\Plugin\views\PluginBase.
 */
class PluginBaseUnitTest extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Plugin base unit tests',
      'description' => 'Tests code of the views plugin base class.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Tests the unpackOptions method.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::unpackOptions.
   */
  public function testUnpackOptions() {
    $plugin = $this->getTestPlugin();

    $test_parameters = array();
    // Set a storage but no value, so the storage value should be kept.
    $test_parameters[] = array(
      'storage' => array(
        'key' => 'value',
      ),
      'options' => array(
      ),
      'definition' => array(
        'key' => array('default' => 'value2'),
      ),
      'expected' => array(
        'key' => 'value',
      ),
    );
    // Set a storage and a option value, so the option value should be kept.
    $test_parameters[] = array(
      'storage' => array(
        'key' => 'value',
      ),
      'options' => array(
        'key' => 'value2',
      ),
      'definition' => array(
        'key' => array('default' => 'value3'),
      ),
      'expected' => array(
        'key' => 'value2',
      ),
      ''
    );
    // Set no storage but an options value, so the options value should be kept.
    $test_parameters[] = array(
      'options' => array(
        'key' => 'value',
      ),
      'definition' => array(
        'key' => array('default' => 'value2'),
      ),
      'expected' => array(
        'key' => 'value',
      ),
    );
    // Set additional options, which aren't part of the definition, so they
    // should be ignored if all is set.
    $test_parameters[] = array(
      'options' => array(
        'key' => 'value',
        'key2' => 'value2',
      ),
      'definition' => array(
        'key' => array('default' => 'value2'),
      ),
      'expected' => array(
        'key' => 'value',
      ),
    );
    $test_parameters[] = array(
      'options' => array(
        'key' => 'value',
        'key2' => 'value2',
      ),
      'definition' => array(
        'key' => array('default' => 'value2'),
      ),
      'expected' => array(
        'key' => 'value',
        'key2' => 'value2',
      ),
      'all' => TRUE,
    );
    // Provide multiple options with their corresponding definition.
    $test_parameters[] = array(
      'options' => array(
        'key' => 'value',
        'key2' => 'value2',
      ),
      'definition' => array(
        'key' => array('default' => 'value2'),
        'key2' => array('default' => 'value3'),
      ),
      'expected' => array(
        'key' => 'value',
        'key2' => 'value2',
      ),
    );
    // Set a complex definition structure with a zero and a one level structure.
    $test_parameters[] = array(
      'options' => array(
        'key0' => 'value',
        'key1' => array('key1:1' => 'value1', 'key1:2' => 'value2'),
      ),
      'definition' => array(
        'key0' => array('default' => 'value0'),
        'key1' => array('contains' => array(
          'key1:1' => array('default' => 'value1:1'),
        )),
      ),
      'expected' => array(
        'key0' => 'value',
        'key1' => array('key1:1' => 'value1'),
      ),
    );
    // Setup a two level structure.
    $test_parameters[] = array(
      'options' => array(
        'key2' => array(
          'key2:1' => array(
            'key2:1:1' => 'value0',
            'key2:1:2' => array(
              'key2:1:2:1' => 'value1',
            ),
          ),
        ),
      ),
      'definition' => array(
        'key2' => array('contains' => array(
          'key2:1' => array('contains' => array(
            'key2:1:1' => array('default' => 'value2:1:2:1'),
            'key2:1:2' => array('contains' => array(
              'key2:1:2:1' => array('default' => 'value2:1:2:1'),
            )),
          )),
        )),
      ),
      'expected' => array(
        'key2' => array(
          'key2:1' => array(
            'key2:1:1' => 'value0',
            'key2:1:2' => array(
              'key2:1:2:1' => 'value1',
            ),
          ),
        ),
      ),
    );

    foreach ($test_parameters as $parameter) {
      $parameter += array(
        'storage' => array(),
      );
      $plugin->unpackOptions($parameter['storage'], $parameter['options'], $parameter['definition'], !empty($parameter['all']));
      $this->assertEqual($parameter['storage'], $parameter['expected']);
    }
  }

  /**
   * Tests the setOptionDefault method.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::setOptionDefaults.
   */
  public function testSetOptionDefault() {
    $plugin = $this->getTestPlugin();

    $test_parameters = array();
    // No definition mustn't change anything on the storage.
    $test_parameters[] = array(
      'definition' => array(),
      'expected' => array(),
    );
    // Set a single definition, which should be picked up.
    $test_parameters[] = array(
      'definition' => array(
        'key' => array('default' => 'value'),
      ),
      'expected' => array(
        'key' => 'value',
      ),
    );
    // Set multiple keys, all should be picked up.
    $test_parameters[] = array(
      'definition' => array(
        'key' => array('default' => 'value'),
        'key2' => array('default' => 'value2'),
        'key3' => array('default' => 'value3'),
      ),
      'expected' => array(
        'key' => 'value',
        'key2' => 'value2',
        'key3' => 'value3',
      ),
    );
    // Setup a definition with multiple levels.
    $test_parameters[] = array(
      'definition' => array(
        'key' => array('default' => 'value'),
        'key2' => array('contains' => array(
          'key2:1' => array('default' => 'value2:1'),
          'key2:2' => array('default' => 'value2:2'),
        )),
      ),
      'expected' => array(
        'key' => 'value',
        'key2' => array(
          'key2:1' => 'value2:1',
          'key2:2' => 'value2:2',
        ),
      ),
    );

    foreach ($test_parameters as $parameter) {
      $parameter += array(
        'storage' => array(),
      );
      $plugin->testSetOptionDefaults($parameter['storage'], $parameter['definition']);
      $this->assertEqual($parameter['storage'], $parameter['expected']);
    }
  }

  /**
   * Sets up and returns a basic instance of a plugin.
   *
   * @return \Drupal\views\Tests\TestHelperPlugin
   *   A test plugin instance.
   */
  protected function getTestPlugin() {
    return new TestHelperPlugin(array(), 'default', array());
  }

}
