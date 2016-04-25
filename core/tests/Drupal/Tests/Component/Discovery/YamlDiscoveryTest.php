<?php

namespace Drupal\Tests\Component\Discovery;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Discovery\YamlDiscovery;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

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
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);
    $url = vfsStream::url('modules');

    mkdir($url . '/test_1');
    file_put_contents($url . '/test_1/test_1.test.yml', 'name: test');
    file_put_contents($url . '/test_1/test_2.test.yml', 'name: test');

    mkdir($url . '/test_2');
    file_put_contents($url . '/test_2/test_3.test.yml', 'name: test');
    // Write an empty YAML file.
    file_put_contents($url . '/test_2/test_4.test.yml', '');

    // Set up the directories to search.
    $directories = array(
      'test_1' => $url . '/test_1',
      'test_2' => $url . '/test_1',
      'test_3' => $url . '/test_2',
      'test_4' => $url . '/test_2',
    );

    $discovery = new YamlDiscovery('test', $directories);
    $data = $discovery->findAll();

    $this->assertEquals(count($data), count($directories));
    $this->assertArrayHasKey('test_1', $data);
    $this->assertArrayHasKey('test_2', $data);
    $this->assertArrayHasKey('test_3', $data);
    $this->assertArrayHasKey('test_4', $data);

    foreach (array('test_1', 'test_2', 'test_3') as $key) {
      $this->assertArrayHasKey('name', $data[$key]);
      $this->assertEquals($data[$key]['name'], 'test');
    }

    $this->assertSame(array(), $data['test_4']);
  }

}
