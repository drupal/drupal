<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockInterfaceTest.
 */

namespace Drupal\block\Tests;

use Drupal\Core\Form\FormState;
use Drupal\simpletest\KernelTestBase;
use Drupal\block\BlockInterface;

/**
 * Tests that the block plugin can work properly without a supporting entity.
 *
 * @group block
 */
class BlockInterfaceTest extends KernelTestBase {
  public static $modules = array('system', 'block', 'block_test', 'user');

  /**
   * Test configuration and subsequent form() and build() method calls.
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
    $configuration = array(
      'label' => 'Custom Display Message',
    );
    $expected_configuration = array(
      'id' => 'test_block_instantiation',
      'label' => 'Custom Display Message',
      'provider' => 'block_test',
      'label_display' => BlockInterface::BLOCK_LABEL_VISIBLE,
      'cache' => array(
        'max_age' => 0,
        'contexts' => array(),
      ),
      'display_message' => 'no message set',
    );
    // Initial configuration of the block at construction time.
    /** @var $display_block \Drupal\Core\Block\BlockPluginInterface */
    $display_block = $manager->createInstance('test_block_instantiation', $configuration);
    $this->assertIdentical($display_block->getConfiguration(), $expected_configuration, 'The block was configured correctly.');

    // Updating an element of the configuration.
    $display_block->setConfigurationValue('display_message', 'My custom display message.');
    $expected_configuration['display_message'] = 'My custom display message.';
    $this->assertIdentical($display_block->getConfiguration(), $expected_configuration, 'The block configuration was updated correctly.');
    $definition = $display_block->getPluginDefinition();

    $period = array(0, 60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400);
    $period = array_map(array(\Drupal::service('date.formatter'), 'formatInterval'), array_combine($period, $period));
    $period[0] = '<' . t('no caching') . '>';
    $period[\Drupal\Core\Cache\Cache::PERMANENT] = t('Forever');
    $contexts = \Drupal::service("cache_contexts")->getLabels();
    unset($contexts['cache_context.theme']);
    unset($contexts['cache_context.language']);
    $expected_form = array(
      'provider' => array(
        '#type' => 'value',
        '#value' => 'block_test',
      ),
      'admin_label' => array(
        '#type' => 'item',
        '#title' => t('Block description'),
        '#markup' => $definition['admin_label'],
      ),
      'label' => array(
        '#type' => 'textfield',
        '#title' => 'Title',
        '#maxlength' => 255,
        '#default_value' => 'Custom Display Message',
        '#required' => TRUE,
      ),
      'label_display' => array(
        '#type' => 'checkbox',
        '#title' => 'Display title',
        '#default_value' => TRUE,
        '#return_value' => 'visible',
      ),
      'cache' => array(
        '#type' => 'details',
        '#title' => t('Cache settings'),
        'max_age' => array(
          '#type' => 'select',
          '#title' => t('Maximum age'),
          '#description' => t('The maximum time this block may be cached.'),
          '#default_value' => 0,
          '#options' => $period,
        ),
        'contexts' => array(
          '#type' => 'checkboxes',
          '#title' => t('Vary by context'),
          '#description' => t('The contexts this cached block must be varied by. <em>All</em> blocks are varied by language and theme.'),
          '#default_value' => array(),
          '#options' => $contexts,
          '#states' => array(
            'disabled' => array(
              ':input[name="settings[cache][max_age]"]' => array('value' => (string) 0),
            ),
          ),
        ),
      ),
      'display_message' => array(
        '#type' => 'textfield',
        '#title' => t('Display message'),
        '#default_value' => 'My custom display message.',
      ),
    );
    $form_state = new FormState();
    // Ensure there are no form elements that do not belong to the plugin.
    $actual_form = $display_block->buildConfigurationForm(array(), $form_state);
    // Remove the visibility sections, as that just tests condition plugins.
    unset($actual_form['visibility'], $actual_form['visibility_tabs']);
    $this->assertIdentical($actual_form, $expected_form, 'Only the expected form elements were present.');

    $expected_build = array(
      '#children' => 'My custom display message.',
    );
    // Ensure the build array is proper.
    $this->assertIdentical($display_block->build(), $expected_build, 'The plugin returned the appropriate build array.');

    // Ensure the machine name suggestion is correct. In truth, this is actually
    // testing BlockBase's implementation, not the interface itself.
    $this->assertIdentical($display_block->getMachineNameSuggestion(), 'displaymessage', 'The plugin returned the expected machine name suggestion.');
  }
}
