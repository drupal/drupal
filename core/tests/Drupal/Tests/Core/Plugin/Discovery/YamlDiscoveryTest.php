<?php

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Discovery\YamlDiscovery
 * @group Plugin
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
  protected $expectedKeys = [
    'test_1' => 'test_1_a',
    'another_provider_1' => 'test_1_b',
    'another_provider_2' => 'test_2_a',
    'test_2' => 'test_2_b',
  ];

  protected function setUp() {
    parent::setUp();

    $base_path = __DIR__ . '/Fixtures';
    // Set up the directories to search.
    $directories = [
      'test_1' => $base_path . '/test_1',
      'test_2' => $base_path . '/test_2',
    ];

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
      foreach (['name', 'id', 'provider'] as $key) {
        $this->assertArrayHasKey($key, $definition);
      }
      $this->assertEquals($id, $definition['id']);
      $this->assertEquals(array_search($id, $this->expectedKeys), $definition['provider']);
    }
  }

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitionsWithTranslatableDefinitions() {
    vfsStream::setup('root');

    $file_1 = <<<'EOS'
test_plugin:
  title: test title
EOS;
    $file_2 = <<<'EOS'
test_plugin2:
  title: test title2
  title_context: 'test-context'
EOS;
    vfsStream::create([
      'test_1' => [
        'test_1.test.yml' => $file_1,
      ],
      'test_2' => [
        'test_2.test.yml' => $file_2,
      ],
    ]);

    $discovery = new YamlDiscovery('test', ['test_1' => vfsStream::url('root/test_1'), 'test_2' => vfsStream::url('root/test_2')]);
    $discovery->addTranslatableProperty('title', 'title_context');
    $definitions = $discovery->getDefinitions();

    $this->assertCount(2, $definitions);
    $plugin_1 = $definitions['test_plugin'];
    $plugin_2 = $definitions['test_plugin2'];

    $this->assertInstanceOf(TranslatableMarkup::class, $plugin_1['title']);
    $this->assertEquals([], $plugin_1['title']->getOptions());
    $this->assertInstanceOf(TranslatableMarkup::class, $plugin_2['title']);
    $this->assertEquals(['context' => 'test-context'], $plugin_2['title']->getOptions());
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
