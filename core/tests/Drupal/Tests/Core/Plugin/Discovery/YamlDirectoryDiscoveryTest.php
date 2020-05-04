<?php

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Component\Discovery\YamlDirectoryDiscovery as ComponentYamlDirectoryDiscovery;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery
 *
 * @group Plugin
 */
class YamlDirectoryDiscoveryTest extends UnitTestCase {

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    vfsStream::setup('modules', NULL, [
      'module_a' => [
        'subdir1' => [
          'plugin1.yml' => "id: plugin1\ntest_provider: module_a",
          'plugin2.yml' => "id: plugin2\ntest_provider: module_a",
        ],
        'subdir2' => [
          'plugin3.yml' => "id: plugin3\ntest_provider: module_a",
        ],
        'subdir3' => [],
      ],
      'module_b' => [
        'subdir1' => [
          'plugin4.yml' => "id: plugin4\ntest_provider: module_b",
        ],
      ],
    ]);
    $directories = [
      'module_a' => [
        vfsStream::url('modules/module_a/subdir1'),
        vfsStream::url('modules/module_a/subdir2'),
        // Empty directory.
        vfsStream::url('modules/module_a/subdir3'),
        // Directory does not exist.
        vfsStream::url('modules/module_a/subdir4'),
      ],
      'module_b' => vfsStream::url('modules/module_b/subdir1'),
    ];
    $discovery = new YamlDirectoryDiscovery($directories, 'test');

    $definitions = $discovery->getDefinitions();

    $this->assertIsArray($definitions);
    $this->assertCount(4, $definitions);

    foreach ($definitions as $id => $definition) {
      foreach (['id', 'provider', ComponentYamlDirectoryDiscovery::FILE_KEY] as $key) {
        $this->assertArrayHasKey($key, $definition);
      }
      $this->assertEquals($id, $definition['id']);
      $this->assertEquals($definition['test_provider'], $definition['provider']);
    }
  }

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitionsWithTranslatableDefinitions() {
    vfsStream::setup('modules', NULL, [
      'module_a' => [
        'subdir1' => [
          'plugin1.yml' => "id: plugin1\ntest_provider: module_a\ntitle: 'test title'",
          'plugin2.yml' => "id: plugin2\ntest_provider: module_a\ntitle: 'test title'\ntitle_context: test-context",
        ],
      ],
    ]);
    $directories = [
      'module_a' => vfsStream::url('modules/module_a/subdir1'),
    ];

    $discovery = new YamlDirectoryDiscovery($directories, 'test');
    $discovery->addTranslatableProperty('title', 'title_context');
    $definitions = $discovery->getDefinitions();

    $this->assertCount(2, $definitions);
    $plugin_1 = $definitions['plugin1'];
    $plugin_2 = $definitions['plugin2'];

    $this->assertInstanceOf(TranslatableMarkup::class, $plugin_1['title']);
    $this->assertEquals([], $plugin_1['title']->getOptions());
    $this->assertInstanceOf(TranslatableMarkup::class, $plugin_2['title']);
    $this->assertEquals(['context' => 'test-context'], $plugin_2['title']->getOptions());
  }

}
