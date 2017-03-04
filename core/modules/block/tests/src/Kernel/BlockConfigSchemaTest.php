<?php

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the block config schema.
 *
 * @group block
 */
class BlockConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'aggregator',
    'book',
    'block_content',
    'comment',
    'forum',
    'node',
    'statistics',
    // BlockManager->getModuleName() calls system_get_info().
    'system',
    'taxonomy',
    'user',
    'text',
  ];

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->typedConfig = \Drupal::service('config.typed');
    $this->blockManager = \Drupal::service('plugin.manager.block');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->installSchema('book', ['book']);
  }

  /**
   * Tests the block config schema for block plugins.
   */
  public function testBlockConfigSchema() {
    foreach ($this->blockManager->getDefinitions() as $block_id => $definition) {
      $id = strtolower($this->randomMachineName());
      $block = Block::create([
        'id' => $id,
        'theme' => 'classy',
        'weight' => 00,
        'status' => TRUE,
        'region' => 'content',
        'plugin' => $block_id,
        'settings' => [
          'label' => $this->randomMachineName(),
          'provider' => 'system',
          'label_display' => FALSE,
        ],
        'visibility' => [],
      ]);
      $block->save();

      $config = $this->config("block.block.$id");
      $this->assertEqual($config->get('id'), $id);
      $this->assertConfigSchema($this->typedConfig, $config->getName(), $config->get());
    }
  }

}
