<?php

declare(strict_types=1);

namespace Drupal\hold_test;

use Drupal\hold_test\EventSubscriber\HoldTestSubscriber;

/**
 * Contains methods for testing hold request/response.
 */
class HoldTestHelper {

  /**
   * Request hold.
   *
   * @param bool $status
   *   TRUE - enable hold, FALSE - disable hold.
   */
  public static function requestHold(bool $status): void {
    $site_path = \Drupal::getContainer()->getParameter('site.path');
    file_put_contents($site_path . '/hold_test_request.txt', $status);
    // If we're releasing the hold wait for a bit to allow the subscriber to
    // read the file.
    if (!$status) {
      usleep(HoldTestSubscriber::WAIT * 2);
    }
  }

  /**
   * Response hold.
   *
   * @param bool $status
   *   TRUE - enable hold, FALSE - disable hold.
   */
  public static function responseHold(bool $status): void {
    $site_path = \Drupal::getContainer()->getParameter('site.path');
    file_put_contents($site_path . '/hold_test_response.txt', $status);
    // If we're releasing the hold wait for a bit to allow the subscriber to
    // read the file.
    if (!$status) {
      usleep(HoldTestSubscriber::WAIT * 2);
    }
  }

}
