<?php

namespace Drupal\node\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * A base class for migrations that require view mode info.
 */
abstract class ViewModeBase extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->initializeIterator());
  }

  /**
   * Get a list of D6 view modes.
   *
   * Drupal 6 supported the following view modes.
   * NODE_BUILD_NORMAL = 0
   * NODE_BUILD_PREVIEW = 1
   * NODE_BUILD_SEARCH_INDEX = 2
   * NODE_BUILD_SEARCH_RESULT = 3
   * NODE_BUILD_RSS = 4
   * NODE_BUILD_PRINT = 5
   * teaser
   * full
   *
   * @return array
   *   The view mode names.
   */
  public function getViewModes() {
    return array(
      0,
      1,
      2,
      3,
      4,
      5,
      'teaser',
      'full',
    );
  }

}
