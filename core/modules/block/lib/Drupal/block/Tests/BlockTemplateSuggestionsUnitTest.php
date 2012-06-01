<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockTemplateSuggestionsUnitTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\UnitTestBase;
use stdClass;

/**
 * Unit tests for template_preprocess_block().
 */
class BlockTemplateSuggestionsUnitTest extends UnitTestBase {
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
    // Define block delta with underscore to be preprocessed
    $block1 = new stdClass();
    $block1->module = 'block';
    $block1->delta = 'underscore_test';
    $block1->region = 'footer';
    $variables1 = array();
    $variables1['elements']['#block'] = $block1;
    $variables1['elements']['#children'] = '';
    template_preprocess_block($variables1);
    $this->assertEqual($variables1['theme_hook_suggestions'], array('block__footer', 'block__block', 'block__block__underscore_test'), t('Found expected block suggestions for delta with underscore'));

    // Define block delta with hyphens to be preprocessed. Hyphens should be
    // replaced with underscores.
    $block2 = new stdClass();
    $block2->module = 'block';
    $block2->delta = 'hyphen-test';
    $block2->region = 'footer';
    $variables2 = array();
    $variables2['elements']['#block'] = $block2;
    $variables2['elements']['#children'] = '';
    // Test adding a class to the block content.
    $variables2['content_attributes_array']['class'][] = 'test-class';
    template_preprocess_block($variables2);
    $this->assertEqual($variables2['theme_hook_suggestions'], array('block__footer', 'block__block', 'block__block__hyphen_test'), t('Hyphens (-) in block delta were replaced by underscore (_)'));
    // Test that the default class and added class are available.
    $this->assertEqual($variables2['content_attributes_array']['class'], array('test-class', 'content'), t('Default .content class added to block content_attributes_array'));
  }
}
