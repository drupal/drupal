<?php

/**
 * @file
 * User page callbacks for tracker.module.
 */

use Drupal\tracker\Controller\TrackerController;

/**
 * Page callback: Generates a page of tracked nodes for the site.
 *
 * Queries the database for info, adds RDFa info if applicable, and generates
 * the render array that will be used to render the page.
 *
 * @param \Drupal\user\UserInterface $account
 *   (optional) The user account to track.
 *
 * @return array
 *   A renderable array.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\tracker\Controller\TrackerController::buildContent() instead.
 *
 * @see https://www.drupal.org/node/3030645
 */
function tracker_page($account = NULL) {
  @trigger_error(__FUNCTION__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\tracker\Controller\TrackerController::buildContent() instead. See https://www.drupal.org/node/3030645', E_USER_DEPRECATED);
  return \Drupal::classResolver(TrackerController::class)->buildContent($account);
}
