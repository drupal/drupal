<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\LocalRedirectResponse.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;

/**
 * Provides a redirect response which cannot redirect to an external URL.
 */
class LocalRedirectResponse extends SecuredRedirectResponse {

  use LocalAwareRedirectResponseTrait {
    LocalAwareRedirectResponseTrait::isLocal as isSafe;
  }

}
