<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Menu\MyAccountMenuLink.
 */

namespace Drupal\user\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Provides custom logic for the user.page menu link.
 */
class MyAccountMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    // The path 'user' must be accessible for anonymous users, but only visible
    // for authenticated users. Authenticated users should see "My account", but
    // anonymous users should not see it at all.
    return (bool) \Drupal::currentUser()->isAnonymous();
  }

}
