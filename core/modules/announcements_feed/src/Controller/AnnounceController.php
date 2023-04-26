<?php

declare(strict_types=1);

namespace Drupal\announcements_feed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\announcements_feed\AnnounceFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for community announcements.
 *
 * @internal
 */
class AnnounceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Constructs an AnnounceController object.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AnnounceController {
    return new static(
      $container->get('announcements_feed.fetcher'),
      $container->getParameter('announcements_feed.feed_link')
    );
  }

  /**
   * Returns the list of Announcements.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A build array with announcements.
   */
  public function getAnnouncements(Request $request): array {
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
    if ($request->query->get('_wrapper_format') != 'drupal_dialog.off_canvas') {
      $build['#theme'] = 'announcements_feed_admin';
      $build['#attached'] = [];
    }

    return $build;
  }

}
