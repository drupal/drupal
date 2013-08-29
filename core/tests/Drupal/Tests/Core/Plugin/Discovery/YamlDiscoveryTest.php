<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Discovery\YamlDiscoveryTest.
 */

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Tests plugin YAML discovery.
 *
 * @see \Drupal\Core\Plugin\Discovery\YamlDiscovery
 */
class YamlDiscoveryTest extends UnitTestCase {

  /**
   * The YamlDiscovery instance to test.
   *
   * @var \Drupal\Core\Plugin\Discovery\YamlDiscovery
   */
  protected $discovery;

  /**
   * Expected provider => key mappings for testing.
   *
   * @var array
   */
  protected $expectedKeys = array(
    'test_1' => 'test_1_a',
    'another_provider_1' => 'test_1_b',
    'another_provider_2' => 'test_2_a',
    'test_2' => 'test_2_b',
  );

  public static function getInfo() {
    return array(
      'name' => 'YamlDiscovery',
      'description' => 'YamlDiscovery unit tests.',
      'group' => 'Plugin',
    );
  }

  public function setUp() {
    parent::setUp();

    $base_path = __DIR__ . '/Fixtures';
    // Set up the directories to search.
    $directories = array(
      'test_1' => $base_path . '/test_1',
      'test_2' => $base_path . '/test_2',
    );

    $this->discovery = new YamlDiscovery('test', $directories);
  }

  /**
   * Tests the getDefinitions() method.
   */
  public function testGetDefinitions() {
    $definitions = $this->discovery->getDefinitions();

    $this->assertInternalType('array', $definitions);
    $this->assertCount(4, $definitions);

    foreach ($this->expectedKeys as $expected_key) {
      $this->assertArrayHasKey($expected_key, $definitions);
    }

    foreach ($definitions as $id => $definition) {
      foreach (array('name', 'id', 'provider') as $key) {
        $this->assertArrayHasKey($key, $definition);
      }
      $this->assertEquals($id, $definition['id']);
      $this->assertEquals(array_search($id, $this->expectedKeys), $definition['provider']);
    }
  }

  /**
   * Tests the getDefinition() method.
   */
  public function testGetDefinition() {
    $definitions = $this->discovery->getDefinitions();
    // Test the getDefinition() method.
    foreach ($this->expectedKeys as $expected_key) {
      $this->assertEquals($definitions[$expected_key], $this->discovery->getDefinition($expected_key));
    }
  }

}
