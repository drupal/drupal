<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\PluginBaseTest.
 */

namespace Drupal\Tests\views\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Tests\TestHelperPlugin;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\PluginBase
 * @group views
 */
class PluginBaseTest extends UnitTestCase {

  /**
   * The test helper plugin to use for the tests.
   *
   * @var \Drupal\views\Tests\TestHelperPlugin
   */
  protected $testHelperPlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testHelperPlugin = new TestHelperPlugin(array(), 'default', array());
  }

  /**
   * Tests the unpackOptions method.
   *
   * @param array $storage
   *   The storage array to unpack option into.
   * @param array $options
   *   The array of options to unpack.
   * @param array $definition
   *   The definition array, defining default options.
   * @param array $expected
   *   The expected array after unpacking
   * @param bool $all
   *   Whether to unpack all options.
   *
   * @dataProvider providerTestUnpackOptions
   * @covers ::unpackOptions
   */
  public function testUnpackOptions($storage, $options, $definition, $expected, $all = FALSE) {
    $this->testHelperPlugin->unpackOptions($storage, $options, $definition, $all);
    $this->assertEquals($storage, $expected);
  }

  /**
   * Tests the setOptionDefault method.
   *
   * @param array $storage
   *   The storage array to unpack option into.
   * @param array $definition
   *   The definition array, defining default options.
   * @param array $expected
   *   The expected array after unpacking
   *
   * @dataProvider providerTestSetOptionDefault
   * @covers ::setOptionDefaults
   */
  public function testSetOptionDefault($storage, $definition, $expected) {
    $this->testHelperPlugin->testSetOptionDefaults($storage, $definition);
    $this->assertEquals($storage, $expected);
  }

  /**
   * Data provider for testUnpackOptions().
   *
   * @return array
   */
  public function providerTestUnpackOptions() {
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
      'storage' => array(),
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
      'storage' => array(),
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
      'storage' => array(),
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
      'storage' => array(),
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
      'storage' => array(),
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
      'storage' => array(),
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

    return $test_parameters;
  }

  /**
   * Data provider for testSetOptionDefault().
   *
   * @return array
   */
  public function providerTestSetOptionDefault() {
    $test_parameters = array();
    // No definition should change anything on the storage.
    $test_parameters[] = array(
      'storage' => array(),
      'definition' => array(),
      'expected' => array(),
    );
    // Set a single definition, which should be picked up.
    $test_parameters[] = array(
      'storage' => array(),
      'definition' => array(
        'key' => array('default' => 'value'),
      ),
      'expected' => array(
        'key' => 'value',
      ),
    );
    // Set multiple keys, all should be picked up.
    $test_parameters[] = array(
      'storage' => array(),
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
      'storage' => array(),
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

    return $test_parameters;
  }

  /**
   * @dataProvider providerTestFilterByDefinedOptions
   * @covers ::filterByDefinedOptions
   */
  public function testFilterByDefinedOptions($storage, $options, $expected_storage) {
    $this->testHelperPlugin->setDefinedOptions($options);
    $this->testHelperPlugin->filterByDefinedOptions($storage);
    $this->assertEquals($expected_storage, $storage);
  }

  public function providerTestFilterByDefinedOptions() {
    $data = [];

    // A simple defined option.
    $values_1 = ['key1' => 'value1'];
    $options_1 = ['key1' => ['default' => '']];
    $data[] = [$values_1, $options_1, $values_1];
    // Multiple defined options .
    $values_2 = ['key1' => 'value1', 'key2' => 'value2'];
    $options_2 = ['key1' => ['default' => ''], 'key2' => ['default' => '']];
    $data[] = [$values_2, $options_2, $values_2];

    // Multiple options, just one defined.
    $data[] = [$values_2, $options_1, $values_1];

    // Nested options, all properly defined.
    $data[] = [['sub1' => $values_2, 'sub2' => $values_2], ['sub1' => ['contains' => $options_2], 'sub2' => ['contains' => $options_2]], ['sub1' => $values_2, 'sub2' => $values_2]];

    // Nested options, not all properly defined.
    $data[] = [['sub1' => $values_2, 'sub2' => $values_2], ['sub1' => ['contains' => $options_2], 'sub2' => ['contains' => $options_1]], ['sub1' => $values_2, 'sub2' => $values_1]];

    return $data;
  }

}
