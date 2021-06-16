<?php

namespace Drupal\Tests\block\Kernel;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the block plugin can work properly without a supporting entity.
 *
 * @group block
 */
class BlockInterfaceTest extends KernelTestBase {

  protected static $modules = ['system', 'block', 'block_test', 'user'];

  /**
   * Tests configuration and subsequent form() and build() method calls.
   *
   * This test is attempting to test the existing block plugin api and all
   * functionality that is expected to remain consistent. The arrays that are
   * used for comparison can change, but only to include elements that are
   * contained within BlockBase or the plugin being tested. Likely these
   * comparison arrays should get smaller, not larger, as more form/build
   * elements are moved into a more suitably responsible class.
   *
   * Instantiation of the plugin is the primary element being tested here. The
   * subsequent method calls are just attempting to cause a failure if a
   * dependency outside of the plugin configuration is required.
   */
  public function testBlockInterface() {
    $manager = $this->container->get('plugin.manager.block');
    $configuration = [
      'label' => 'Custom Display Message',
    ];
    $expected_configuration = [
      'id' => 'test_block_instantiation',
      'label' => 'Custom Display Message',
      'provider' => 'block_test',
      'label_display' => BlockPluginInterface::BLOCK_LABEL_VISIBLE,
      'display_message' => 'no message set',
    ];
    // Initial configuration of the block at construction time.
    /** @var \Drupal\Core\Block\BlockPluginInterface $display_block */
    $display_block = $manager->createInstance('test_block_instantiation', $configuration);
    $this->assertSame($expected_configuration, $display_block->getConfiguration(), 'The block was configured correctly.');

    // Updating an element of the configuration.
    $display_block->setConfigurationValue('display_message', 'My custom display message.');
    $expected_configuration['display_message'] = 'My custom display message.';
    $this->assertSame($expected_configuration, $display_block->getConfiguration(), 'The block configuration was updated correctly.');
    $definition = $display_block->getPluginDefinition();

    $expected_form = [
      'provider' => [
        '#type' => 'value',
        '#value' => 'block_test',
      ],
      'admin_label' => [
        '#type' => 'item',
        '#title' => t('Block description'),
        '#plain_text' => $definition['admin_label'],
      ],
      'label' => [
        '#type' => 'textfield',
        '#title' => 'Title',
        '#maxlength' => 255,
        '#default_value' => 'Custom Display Message',
        '#required' => TRUE,
      ],
      'label_display' => [
        '#type' => 'checkbox',
        '#title' => 'Display title',
        '#default_value' => TRUE,
        '#return_value' => 'visible',
      ],
      'context_mapping' => [],
      'display_message' => [
        '#type' => 'textfield',
        '#title' => t('Display message'),
        '#default_value' => 'My custom display message.',
      ],
    ];
    $form_state = new FormState();
    // Ensure there are no form elements that do not belong to the plugin.
    $actual_form = $display_block->buildConfigurationForm([], $form_state);
    // Remove the visibility sections, as that just tests condition plugins.
    unset($actual_form['visibility'], $actual_form['visibility_tabs']);
    $this->assertEquals($expected_form, $actual_form, 'Only the expected form elements were present.');

    $expected_build = [
      '#children' => 'My custom display message.',
    ];
    // Ensure the build array is proper.
    $this->assertSame($expected_build, $display_block->build(), 'The plugin returned the appropriate build array.');

    // Ensure the machine name suggestion is correct. In truth, this is actually
    // testing BlockBase's implementation, not the interface itself.
    $this->assertSame('displaymessage', $display_block->getMachineNameSuggestion(), 'The plugin returned the expected machine name suggestion.');
  }

}
