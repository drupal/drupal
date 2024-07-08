<?php

namespace Drupal\update\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Site\Settings;

/**
 * Determines whether allow authorized operations is set.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3458658
 */
class UpdateManagerAccessCheck implements AccessInterface {

  /**
   * Settings Service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs an UpdateManagerAccessCheck object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The read-only settings container.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * Checks access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3458658', E_USER_DEPRECATED);
    // Uncacheable because the access result depends on a Settings key-value
    // pair, and can therefore change at any time.
    return AccessResult::allowedIf($this->settings->get('allow_authorize_operations', TRUE))->setCacheMaxAge(0);
  }

}
