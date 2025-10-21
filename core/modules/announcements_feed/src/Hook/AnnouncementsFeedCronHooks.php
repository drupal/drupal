<?php

namespace Drupal\announcements_feed\Hook;

use Drupal\announcements_feed\AnnounceFetcher;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Cron hook implementations for announcements_feed.
 */
class AnnouncementsFeedCronHooks {

  public function __construct(
    protected readonly AnnounceFetcher $announceFetcher,
    protected readonly StateInterface $state,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $interval = $this->configFactory->get('announcements_feed.settings')->get('cron_interval');
    $last_check = $this->state->get('announcements_feed.last_fetch', 0);
    $time = $this->time->getRequestTime();
    if ($time - $last_check > $interval) {
      $this->announceFetcher->fetch(TRUE);
      $this->state->set('announcements_feed.last_fetch', $time);
    }
  }

}
