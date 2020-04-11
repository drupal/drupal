<?php

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * YamlDiscoveryDecorator unit tests.
 *
 * @group Plugin
 */
class YamlDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * The YamlDiscovery instance to test.
   *
   * @var \Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator
   */
  protected $discoveryDecorator;

  /**
   * Expected provider => key mappings for testing.
   *
   * @var array
   */
  protected $expectedKeys = [
    'test_1' => 'test_1_a',
    'another_provider_1' => 'test_1_b',
    'another_provider_2' => 'test_2_a',
    'test_2' => 'test_2_b',
    'decorated_1' => 'decorated_test_1',
    'decorated_2' => 'decorated_test_2',
  ];

  protected function setUp() {
    parent::setUp();

    $base_path = __DIR__ . '/Fixtures';
    // Set up the directories to search.
    $directories = [
      'test_1' => $base_path . '/test_1',
      'test_2' => $base_path . '/test_2',
    ];

    $definitions = [
      'decorated_test_1' => [
        'id' => 'decorated_test_1',
        'name' => 'Decorated test 1',
        'provider' => 'decorated_1',
      ],
      'decorated_test_2' => [
        'id' => 'decorated_test_2',
        'name' => 'Decorated test 1',
        'provider' => 'decorated_2',
      ],
    ];

    $decorated = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $decorated->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->discoveryDecorator = new YamlDiscoveryDecorator($decorated, 'test', $directories);
  }

  /**
   * Tests the getDefinitions() method.
   */
  public function testGetDefinitions() {
    $definitions = $this->discoveryDecorator->getDefinitions();

    $this->assertIsArray($definitions);
    $this->assertCount(6, $definitions);

    foreach ($this->expectedKeys as $expected_key) {
      $this->assertArrayHasKey($expected_key, $definitions);
    }

    foreach ($definitions as $id => $definition) {
      foreach (['name', 'id', 'provider'] as $key) {
        $this->assertArrayHasKey($key, $definition);
      }
      $this->assertEquals($id, $definition['id']);
      $this->assertEquals(array_search($id, $this->expectedKeys), $definition['provider']);
    }
  }

}
