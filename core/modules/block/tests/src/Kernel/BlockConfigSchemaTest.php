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
  protected static $modules = [
    'block',
    'block_content',
    'comment',
    'node',
    // \Drupal\block\Entity\Block->preSave() calls system_region_list().
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
  protected function setUp(): void {
    parent::setUp();

    $this->typedConfig = \Drupal::service('config.typed');
    $this->blockManager = \Drupal::service('plugin.manager.block');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->container->get('theme_installer')->install(['stark']);
  }

  /**
   * Tests the block config schema for block plugins.
   */
  public function testBlockConfigSchema() {
    foreach ($this->blockManager->getDefinitions() as $block_id => $definition) {
      $id = $this->randomMachineName();
      $block = Block::create([
        'id' => $id,
        'theme' => 'stark',
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
      $this->assertEquals($id, $config->get('id'));
      $this->assertConfigSchema($this->typedConfig, $config->getName(), $config->get());
    }
  }

}
