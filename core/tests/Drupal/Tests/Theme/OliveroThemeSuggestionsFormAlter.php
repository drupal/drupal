<?php

namespace Drupal\Tests\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's hook_theme_suggestions_HOOK_alter.
 *
 * @group olivero
 */
final class OliveroThemeSuggestionsFormAlter extends UnitTestCase {

  /**
   * Tests the search block form has a theme suggestions.
   */
  public function testThemeSuggestionForForm() {
    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';
    $suggestions = [];
    $variables = [
      'element' => [
        '#form_id' => 'search_block_form',
      ],
    ];
    olivero_theme_suggestions_form_alter($suggestions, $variables);
    self::assertEquals(['form__search_block_form'], $suggestions);

  }

}
