<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockPreprocessUnitTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Web tests for template_preprocess_block().
 */
class BlockPreprocessUnitTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Block preprocess',
      'description' => 'Test the template_preprocess_block() function.',
      'group' => 'Block',
    );
  }

  /**
   * Tests block classes with template_preprocess_block().
   */
  function testBlockClasses() {
    // Define a block with a derivative to be preprocessed, which includes both
    // an underscore (not transformed) and a hyphen (transformed to underscore),
    // and generates possibilities for each level of derivative.
    // @todo Clarify this comment.
    $block = entity_create('block', array(
      'plugin' => 'system_menu_block:admin',
      'region' => 'footer',
      'id' => \Drupal::config('system.theme')->get('default') . '.machinename',
    ));

    $variables = array();
    $variables['elements']['#block'] = $block;
    $plugin = $block->getPlugin();
    $variables['elements']['#configuration'] = $plugin->getConfiguration();
    $variables['elements']['#plugin_id'] = $plugin->getPluginId();
    $variables['elements']['#base_plugin_id'] = $plugin->getBasePluginId();
    $variables['elements']['#derivative_plugin_id'] = $plugin->getDerivativeId();
    $variables['elements']['content'] = array();

    // Test adding a class to the block content.
    $variables['content_attributes']['class'][] = 'test-class';
    template_preprocess_block($variables);
    $this->assertEqual($variables['content_attributes']['class'], array('test-class', 'content'), 'Default .content class added to block content_attributes');
  }

}
