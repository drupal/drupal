<?php

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Plugin\PluginBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests block content plugin deriver.
 *
 * @group block_content
 */
class BlockContentDeriverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'block_content', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequence']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
  }

  /**
   * Tests that only reusable blocks are derived.
   */
  public function testReusableBlocksOnlyAreDerived() {
    // Create a block content type.
    $block_content_type = BlockContentType::create([
      'id' => 'spiffy',
      'label' => 'Mucho spiffy',
      'description' => "Provides a block type that increases your site's spiffiness by up to 11%",
    ]);
    $block_content_type->save();
    // And a block content entity.
    $block_content = BlockContent::create([
      'info' => 'Spiffy prototype',
      'type' => 'spiffy',
    ]);
    $block_content->save();

    // Ensure the reusable block content is provided as a derivative block
    // plugin.
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');
    $plugin_id = 'block_content' . PluginBase::DERIVATIVE_SEPARATOR . $block_content->uuid();
    $this->assertTrue($block_manager->hasDefinition($plugin_id));

    // Set the block not to be reusable.
    $block_content->setNonReusable();
    $block_content->save();

    // Ensure the non-reusable block content is not provided a derivative block
    // plugin.
    $this->assertFalse($block_manager->hasDefinition($plugin_id));
  }

}
