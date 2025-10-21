<?php

namespace Drupal\announcements_feed\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for announcements_feed.
 */
class AnnouncementsFeedThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    return [
      'announcements_feed' => [
        'variables' => [
          'featured' => NULL,
          'standard' => NULL,
          'count' => 0,
          'feed_link' => '',
        ],
      ],
      'announcements_feed_admin' => [
        'variables' => [
          'featured' => NULL,
          'standard' => NULL,
          'count' => 0,
          'feed_link' => '',
        ],
      ],
    ];
  }

}
