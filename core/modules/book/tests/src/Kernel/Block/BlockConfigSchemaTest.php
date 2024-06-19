<?php

declare(strict_types=1);

namespace Drupal\Tests\book\Kernel\Block;

use Drupal\block\Entity\Block;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the block config schema.
 *
 * @group book
 */
class BlockConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'book',
    'node',
    // \Drupal\block\Entity\Block->preSave() calls system_region_list().
    'system',
    'user',
  ];

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedConfig = \Drupal::service('config.typed');
    $this->installEntitySchema('node');
    $this->installSchema('book', ['book']);
    $this->container->get('theme_installer')->install(['stark']);
  }

  /**
   * Tests the block config schema for block plugins.
   */
  public function testBlockConfigSchema(): void {
    $id = strtolower($this->randomMachineName());
    $block = Block::create([
      'id' => $id,
      'theme' => 'stark',
      'weight' => 00,
      'status' => TRUE,
      'region' => 'content',
      'plugin' => 'book_navigation',
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
