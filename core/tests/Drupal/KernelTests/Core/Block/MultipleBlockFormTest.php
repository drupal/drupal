<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Block;

use Drupal\block_test\PluginForm\EmptyBlockForm;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that blocks can have multiple forms.
 *
 * @group block
 */
class MultipleBlockFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'block_test'];

  /**
   * Tests that blocks can have multiple forms.
   */
  public function testMultipleForms(): void {
    $configuration = ['label' => 'A very cool block'];
    $block = \Drupal::service('plugin.manager.block')->createInstance('test_multiple_forms_block', $configuration);

    $form_object1 = \Drupal::service('plugin_form.factory')->createInstance($block, 'configure');
    $form_object2 = \Drupal::service('plugin_form.factory')->createInstance($block, 'secondary');

    // Assert that the block itself is used for the default form.
    $this->assertSame($block, $form_object1);

    // Ensure that EmptyBlockForm is used and the plugin is set.
    $this->assertInstanceOf(EmptyBlockForm::class, $form_object2);
    $this->assertEquals($block, $form_object2->plugin);
  }

}
