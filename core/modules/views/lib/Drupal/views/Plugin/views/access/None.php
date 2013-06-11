<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\None.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides no access control at all.
 *
 * @ingroup views_access_plugins
 *
 * @Plugin(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("Will be available to all users.")
 * )
 */
class None extends AccessPluginBase {

  public function summaryTitle() {
    return t('Unrestricted');
  }

  /**
   * Implements Drupal\views\Plugin\views\access\AccessPluginBase::access().
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
