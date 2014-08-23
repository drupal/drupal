<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockConfigSchemaTest.
 */

namespace Drupal\block\Tests;

use Drupal\block\Entity\Block;
use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\simpletest\KernelTestBase;

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
  public static $modules = array(
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
  );

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
  }

  /**
   * Tests the block config schema for block plugins.
   */
  public function testBlockConfigSchema() {
    foreach ($this->blockManager->getDefinitions() as $block_id => $definition) {
      $id = strtolower($this->randomMachineName());
      $block = Block::create(array(
        'id' => $id,
        'theme' => 'stark',
        'weight' => 00,
        'status' => TRUE,
        'region' => 'content',
        'plugin' => $block_id,
        'settings' => array(
          'label' => $this->randomMachineName(),
          'provider' => 'system',
          'label_display' => FALSE,
        ),
        'visibility' => array(),
      ));
      $block->save();

      $config = \Drupal::config("block.block.$id");
      $this->assertEqual($config->get('id'), $id);
      $this->assertConfigSchema($this->typedConfig, $config->getName(), $config->get());
    }
  }

}
