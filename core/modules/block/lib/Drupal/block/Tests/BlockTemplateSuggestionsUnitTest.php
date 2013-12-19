<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockTemplateSuggestionsUnitTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for block_theme_suggestions_block().
 */
class BlockTemplateSuggestionsUnitTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Block template suggestions',
      'description' => 'Test the block_theme_suggestions_block() function.',
      'group' => 'Block',
    );
  }

  /**
   * Tests template suggestions from block_theme_suggestions_block().
   */
  function testBlockThemeHookSuggestions() {
    // Define a block with a derivative to be preprocessed, which includes both
    // an underscore (not transformed) and a hyphen (transformed to underscore),
    // and generates possibilities for each level of derivative.
    // @todo Clarify this comment.
    $block = entity_create('block', array(
      'plugin' => 'system_menu_block:admin',
      'region' => 'footer',
      'id' => 'machinename',
    ));

    $variables = array();
    $variables['elements']['#block'] = $block;
    $plugin = $block->getPlugin();
    $variables['elements']['#configuration'] = $plugin->getConfiguration();
    $variables['elements']['#plugin_id'] = $plugin->getPluginId();
    $variables['elements']['#base_plugin_id'] = $plugin->getBasePluginId();
    $variables['elements']['#derivative_plugin_id'] = $plugin->getDerivativeId();
    $variables['elements']['content'] = array();
    $suggestions = block_theme_suggestions_block($variables);
    $this->assertEqual($suggestions, array('block__system', 'block__system_menu_block', 'block__system_menu_block__admin', 'block__machinename'));
  }

}
