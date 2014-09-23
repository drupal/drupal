<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\None.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides no access control at all.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("Will be available to all users.")
 * )
 */
class None extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Unrestricted');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // No access control.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_access', 'TRUE');
  }

}
