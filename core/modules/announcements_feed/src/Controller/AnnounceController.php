<?php

declare(strict_types=1);

namespace Drupal\announcements_feed\Controller;

use Drupal\announcements_feed\AnnounceRenderer;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
   * @param \Drupal\announcements_feed\AnnounceRenderer $announceRenderer
   *   The AnnounceRenderer service.
   */
  public function __construct(
    protected AnnounceRenderer $announceRenderer,
  ) {
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
    $build = $this->announceRenderer->render();
    if ($request->query->get('_wrapper_format') != 'drupal_dialog.off_canvas') {
      $build['#theme'] = 'announcements_feed_admin';
      $build['#attached'] = [];
    }

    return $build;
  }

}
