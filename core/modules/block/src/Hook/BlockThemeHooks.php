<?php

namespace Drupal\block\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for block.
 */
class BlockThemeHooks {

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_block')]
  public function themeSuggestionsBlock(array $variables): array {
    $suggestions = [];
    $suggestions[] = 'block__' . $variables['elements']['#configuration']['provider'];
    // Hyphens (-) and underscores (_) play a special role in theme
    // suggestions. Theme suggestions should only contain underscores, because
    // within drupal_find_theme_templates(), underscores are converted to
    // hyphens to match template file names, and then converted back to
    // underscores to match pre-processing and other function names. So if your
    // theme suggestion contains a hyphen, it will end up as an underscore
    // after this conversion, and your function names won't be recognized. So,
    // we need to convert hyphens to underscores in block deltas for the theme
    // suggestions. We can safely explode on : because we know the Block plugin
    // type manager enforces that delimiter for all derivatives.
    $parts = explode(':', $variables['elements']['#plugin_id']);
    $suggestion = 'block';
    while ($part = array_shift($parts)) {
      $suggestions[] = $suggestion .= '__' . strtr($part, '-', '_');
    }
    if (!empty($variables['elements']['#id'])) {
      $suggestions[] = 'block__' . $variables['elements']['#id'];
    }
    return $suggestions;
  }

}
