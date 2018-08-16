<?php

namespace Drupal\Tests\block\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the block_theme_suggestions_block() function.
 *
 * @group block
 */
class BlockTemplateSuggestionsTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * Tests template suggestions from block_theme_suggestions_block().
   */
  public function testBlockThemeHookSuggestions() {
    // Define a block with a derivative to be preprocessed, which includes both
    // an underscore (not transformed) and a hyphen (transformed to underscore),
    // and generates possibilities for each level of derivative.
    // @todo Clarify this comment.
    $block = Block::create([
      'plugin' => 'system_menu_block:admin',
      'region' => 'footer',
      'id' => 'machinename',
    ]);

    $variables = [];
    $plugin = $block->getPlugin();
    $variables['elements']['#configuration'] = $plugin->getConfiguration();
    $variables['elements']['#plugin_id'] = $plugin->getPluginId();
    $variables['elements']['#id'] = $block->id();
    $variables['elements']['#base_plugin_id'] = $plugin->getBaseId();
    $variables['elements']['#derivative_plugin_id'] = $plugin->getDerivativeId();
    $variables['elements']['content'] = [];
    $suggestions = block_theme_suggestions_block($variables);
    $this->assertEqual($suggestions, ['block__system', 'block__system_menu_block', 'block__system_menu_block__admin', 'block__machinename']);
  }

}
