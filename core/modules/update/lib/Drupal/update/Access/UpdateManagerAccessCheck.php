<?php

/**
 * @file
 * Contains Drupal\update\Access\UpdateManagerAccessCheck.
 */

namespace Drupal\update\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines whether allow authorized operations is set.
 */
class UpdateManagerAccessCheck implements AccessInterface {

  /**
   * Settings Service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a UpdateManagerAccessCheck object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The read-only settings container.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $this->settings->get('allow_authorize_operations', TRUE) ? static::ALLOW : static::DENY;
  }

}
