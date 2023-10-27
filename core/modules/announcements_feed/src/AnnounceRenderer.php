<?php

declare(strict_types=1);

namespace Drupal\announcements_feed;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service to render announcements from the external feed.
 *
 * @internal
 */
final class AnnounceRenderer {

  use StringTranslationTrait;

  /**
   * Constructs an AnnouncementRenderer object.
   *
   * @param \Drupal\announcements_feed\AnnounceFetcher $announceFetcher
   *   The AnnounceFetcher service.
   * @param string $feedLink
   *   The feed url path.
   */
  public function __construct(
    protected AnnounceFetcher $announceFetcher,
    protected string $feedLink
  ) {
  }

  /**
   * Generates the announcements feed render array.
   *
   * @return array
   *   Render array containing the announcements feed.
   */
  public function render(): array {
    try {
      $announcements = $this->announceFetcher->fetch();
    }
    catch (\Exception $e) {
      return [
        '#theme' => 'status_messages',
        '#message_list' => [
          'error' => [
            $this->t('An error occurred while parsing the announcements feed, check the logs for more information.'),
          ],
        ],
        '#status_headings' => [
          'error' => $this->t('Error Message'),
        ],
      ];
    }

    $build = [];
    foreach ($announcements as $announcement) {
      $key = $announcement->featured ? '#featured' : '#standard';
      $build[$key][] = $announcement;
    }

    $build += [
      '#theme' => 'announcements_feed',
      '#count' => count($announcements),
      '#feed_link' => $this->feedLink,
      '#cache' => [
        'contexts' => [
          'url.query_args:_wrapper_format',
        ],
        'tags' => [
          'announcements_feed:feed',
        ],
      ],
      '#attached' => [
        'library' => [
          'announcements_feed/drupal.announcements_feed.dialog',
        ],
      ],
    ];

    return $build;
  }

}
