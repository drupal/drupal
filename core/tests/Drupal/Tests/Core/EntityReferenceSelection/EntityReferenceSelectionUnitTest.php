<?php

namespace Drupal\Tests\Core\EntityReferenceSelection;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Tests\UnitTestCase;

/**
 * Provides unit testing for selection handlers.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase
 *
 * @group entity_reference
 * @group legacy
 */
class EntityReferenceSelectionUnitTest extends UnitTestCase {

  /**
   * Tests invalid default configuration.
   *
   * @covers ::defaultConfiguration
   * @covers ::resolveBackwardCompatibilityConfiguration
   */
  public function testInvalidDefaultConfiguration() {
    $this->setExpectedException(\InvalidArgumentException::class, "TestSelectionWithInvalidDefaultConfiguration::defaultConfiguration() should not contain a 'handler_settings' key. All settings should be placed in the root level.");
    new TestSelectionWithInvalidDefaultConfiguration(
      [],
      'test_selector',
      ['class' => 'TestSelectionWithInvalidDefaultConfiguration']
    );
  }

  /**
   * Tests the selection handler with malformed 'handler_settings' value.
   *
   * @covers ::setConfiguration
   * @covers ::resolveBackwardCompatibilityConfiguration
   */
  public function testMalformedHandlerSettingsValue() {
    $this->setExpectedException(\InvalidArgumentException::class, "The setting 'handler_settings' is reserved and cannot be used.");
    new TestSelection(
      // The deprecated 'handler_setting' should be an array.
      ['handler_settings' => FALSE],
      'test_selector',
      ['class' => 'TestSelectionWithInvalidDefaultConfiguration']
    );
  }

  /**
   * Provides test data for ::testSetConfiguration()
   *
   * @return array
   *
   * @see \Drupal\Tests\Core\EntityReferenceSelection\testSetConfiguration
   */
  public function providerTestSetConfiguration() {
    return [
      [
        [
          'setting1' => 'foo',
          'setting2' => [
            'bar' => 'bar value',
            'baz' => 'baz value',
          ],
        ],
      ],
      [
        [
          'handler_settings' => [
            'setting1' => 'foo',
            'setting2' => [
              'bar' => 'bar value',
              'baz' => 'baz value',
            ],
          ],
        ],
      ],
      [
        [
          'setting1' => 'foo',
          'handler_settings' => [
            'setting2' => [
              'bar' => 'bar value',
              'baz' => 'baz value',
            ],
          ],
        ],
      ],
      [
        [
          'setting1' => 'foo',
          'setting2' => [
            'bar' => 'bar value',
            'baz' => 'baz value',
          ],
          'handler_settings' => [
            // Same setting from root level takes precedence.
            'setting2' => 'this will be overwritten',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests selection handler plugin configuration set.
   *
   * @dataProvider providerTestSetConfiguration
   * @covers ::setConfiguration
   * @covers ::resolveBackwardCompatibilityConfiguration
   * @covers ::ensureBackwardCompatibilityConfiguration
   *
   * @param array $options
   *   The configuration passed to the plugin.
   */
  public function testSetConfiguration($options) {
    $selection = new TestSelection($options, 'test_selector', []);

    $expected = [
      'target_type' => NULL,
      'handler' => 'test_selector',
      'entity' => NULL,
      'setting1' => 'foo',
      'setting2' => [
        'qux' => 'qux value',
        'bar' => 'bar value',
        'baz' => 'baz value',
      ],
      'setting3' => 'foobar',
      'handler_settings' => [
        'setting1' => 'foo',
        'setting2' => [
          'qux' => 'qux value',
          'bar' => 'bar value',
          'baz' => 'baz value',
        ],
        'setting3' => 'foobar',
      ],
    ];

    $this->assertArrayEquals($expected, $selection->getConfiguration());
  }

  /**
   * Tests the selection handler plugin BC structure.
   *
   * @covers ::setConfiguration
   * @covers ::resolveBackwardCompatibilityConfiguration
   * @covers ::ensureBackwardCompatibilityConfiguration
   */
  public function testSetConfigurationBcLevel() {
    $config = [
      'target_type' => 'some_entity_type_id',
      'handler' => 'test_selector',
      'setting1' => 'foo',
    ];
    $selection = new TestSelection($config, 'test_selector', []);

    $expected = [
      'target_type' => 'some_entity_type_id',
      'handler' => 'test_selector',
      'entity' => NULL,
      'setting1' => 'foo',
      'setting2' => ['qux' => 'qux value'],
      'setting3' => 'foobar',
      'handler_settings' => [
        'setting1' => 'foo',
        'setting2' => ['qux' => 'qux value'],
        'setting3' => 'foobar',
      ],
    ];

    $this->assertArrayEquals($expected, $selection->getConfiguration());

    // Read the stored values and override a setting.
    $config = $selection->getConfiguration();
    $config['setting1'] = 'bar';
    $selection->setConfiguration($config);
    $expected['setting1'] = 'bar';
    $expected['handler_settings']['setting1'] = 'bar';

    $this->assertArrayEquals($expected, $selection->getConfiguration());
  }

  /**
   * Tests deprecation error triggering.
   *
   * @covers ::setConfiguration
   * @covers ::resolveBackwardCompatibilityConfiguration
   * @expectedDeprecation Providing settings under 'handler_settings' is deprecated and will be removed before 9.0.0. Move the settings in the root of the configuration array. See https://www.drupal.org/node/2870971.
   */
  public function testDeprecationErrorTriggering() {
    // Configuration with BC level.
    $config = ['handler_settings' => ['setting1' => TRUE]];
    new TestSelection($config, 'test_selector', []);
    // Ensure at least one assertion.
    $this->assertTrue(TRUE);
  }

}

/**
 * Provides a testing plugin.
 */
class TestSelection extends SelectionPluginBase {

  public function defaultConfiguration() {
    return [
      'setting2' => [
        'qux' => 'qux value',
      ],
      'setting3' => 'foobar',
    ] + parent::defaultConfiguration();
  }

  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {}

  public function validateReferenceableEntities(array $ids) {}

  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {}

}

/**
 * Provides a testing plugin with invalid default configuration.
 */
class TestSelectionWithInvalidDefaultConfiguration extends TestSelection {

  public function defaultConfiguration() {
    return [
      'handler_settings' => ['foo' => 'bar'],
    ];
  }

}
