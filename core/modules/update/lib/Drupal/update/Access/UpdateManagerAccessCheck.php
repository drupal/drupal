<?php

/**
 * @file
 * Contains Drupal\update\Access\UpdateManagerAccessCheck.
 */

namespace Drupal\update\Access;

use Drupal\Component\Utility\Settings;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines whether allow authorized operations is set.
 */
class UpdateManagerAccessCheck implements StaticAccessCheckInterface {

  /**
   * Settings Service.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

  /**
   * Constructs a UpdateManagerAccessCheck object.
   *
   * @param \Drupal\update\updateManager $update_manager
   *   update Manager Service.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_update_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    return $this->settings->get('allow_authorize_operations', TRUE) ? static::ALLOW : static::DENY;
  }

}
