<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockTemplateSuggestionsUnitTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for template_preprocess_block().
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
      'description' => 'Test the template_preprocess_block() function.',
      'group' => 'Block',
    );
  }

  /**
   * Test if template_preprocess_block() handles the suggestions right.
   */
  function testBlockThemeHookSuggestions() {
    // Define a block with a derivative to be preprocessed, which includes both
    // an underscore (not transformed) and a hyphen (transformed to underscore),
    // and generates possibilities for each level of derivative.
    // @todo Clarify this comment.
    $data = array(
      'region' => 'footer',
    );

    $block = drupal_container()->get('plugin.manager.block')->createInstance('system_menu_block:menu-admin', $data);
    $variables = array();
    $variables['elements']['#block'] = $block;
    $variables['elements']['#children'] = '';
    // Test adding a class to the block content.
    $variables['content_attributes']['class'][] = 'test-class';
    template_preprocess_block($variables);
    $this->assertEqual($variables['theme_hook_suggestions'], array('block__footer', 'block__system', 'block__system_menu_block', 'block__system_menu_block__menu_admin'));
    $this->assertEqual($variables['content_attributes']['class'], array('test-class', 'content'), 'Default .content class added to block content_attributes_array');
  }
}
