<?php

namespace Drupal\announcements_feed\Hook;

use Drupal\announcements_feed\AnnounceFetcher;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Cron hook implementations for announcements_feed.
 */
class AnnouncementsFeedCronHooks {

  public function __construct(
    protected readonly AnnounceFetcher $announceFetcher,
    protected readonly KeyValueFactoryInterface $keyValueFactory,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $interval = $this->configFactory->get('announcements_feed.settings')->get('cron_interval');
    $store = $this->keyValueFactory->get('announcements_feed');
    $last_check = (int) $store->get('last_fetch', 0);
    $time = $this->time->getRequestTime();
    if ($time - $last_check > $interval) {
      $this->announceFetcher->fetch(TRUE);
      $store->set('last_fetch', $time);
    }
  }

}
