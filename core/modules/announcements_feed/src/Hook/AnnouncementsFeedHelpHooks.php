<?php

namespace Drupal\announcements_feed\Hook;

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Help hook implementations for announcements_feed.
 */
class AnnouncementsFeedHelpHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.announcements_feed':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Announcements module displays announcements from the Drupal community. For more information, see the <a href=":documentation">online documentation for the Announcements module</a>.', [
          ':documentation' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/announcements-feed',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl><dt>' . $this->t('Accessing announcements') . '</dt>';
        $output .= '<dd>' . $this->t('Users with the "View drupal.org announcements" permission may click on the "Announcements" item in the administration toolbar, or access @link, to see all announcements relevant to the Drupal version of your site.', [
          '@link' => Link::createFromRoute($this->t('Announcements'), 'announcements_feed.announcement')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
