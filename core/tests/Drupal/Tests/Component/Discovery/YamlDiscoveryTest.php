<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Discovery\YamlDiscoveryTest.
 */

namespace Drupal\Tests\Component\Discovery;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Discovery\YamlDiscovery;

/**
 * YamlDiscovery component unit tests.
 *
 * @group Discovery
 */
class YamlDiscoveryTest extends UnitTestCase {

  /**
   * Tests the YAML file discovery.
   */
  public function testDiscovery() {
    $base_path = __DIR__ . '/Fixtures';
    // Set up the directories to search.
    $directories = array(
      'test_1' => $base_path . '/test_1',
      'test_2' => $base_path . '/test_2',
      // Use the same directory with a different provider name.
      'test_3' => $base_path . '/test_2',
    );

    $discovery = new YamlDiscovery('test', $directories);
    $data = $discovery->findAll();

    $this->assertEquals(count($data), count($directories));
    $this->assertArrayHasKey('test_1', $data);
    $this->assertArrayHasKey('test_2', $data);
    $this->assertArrayHasKey('test_3', $data);

    foreach ($data as $item) {
      $this->assertArrayHasKey('name', $item);
      $this->assertEquals($item['name'], 'test');
    }
  }

}
