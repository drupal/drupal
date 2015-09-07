<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Placeholder\SingleFlushStrategy
 */

namespace Drupal\Core\Render\Placeholder;

/**
 * Defines the 'single_flush' placeholder strategy.
 *
 * This is designed to be the fallback strategy, so should have the lowest
 * priority. All placeholders that are not yet replaced at this point will be
 * rendered as is and delivered directly.
 */
class SingleFlushStrategy implements PlaceholderStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    // Return all placeholders as is; they should be rendered directly.
    return $placeholders;
  }
}
