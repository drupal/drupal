<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Block;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the BlockBase class, base for all block plugins.
 *
 * @group block
 */
class BlockBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'block_test'];

  /**
   * Tests that blocks config form have context mapping, and it is stored in configuration.
   */
  public function testContextMapping(): void {
    $configuration = ['label' => 'A very cool block'];

    /** @var \Drupal\Core\Block\BlockManagerInterface $blockManager */
    $blockManager = \Drupal::service('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockBase $block */
    $block = $blockManager->createInstance('test_block_instantiation', $configuration);

    // Check that context mapping is present in the block config form.
    $form = [];
    $form_state = new FormState();
    $form = $block->buildConfigurationForm($form, $form_state);
    $this->assertArrayHasKey('context_mapping', $form);

    // Check that context mapping is stored in block's configuration.
    $context_mapping = [
      'user' => 'current_user',
    ];
    $form_state->setValue('context_mapping', $context_mapping);
    $block->submitConfigurationForm($form, $form_state);
    $this->assertEquals($context_mapping, $block->getConfiguration()['context_mapping'] ?? NULL);
  }

}
